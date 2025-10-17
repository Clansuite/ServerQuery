<?php declare(strict_types=1);

/**
 * Clansuite Server Query
 *
 * SPDX-FileCopyrightText: 2003-2025 Jens A. Koch
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Clansuite\ServerQuery\ServerProtocols;

use function array_values;
use function count;
use function fclose;
use function feof;
use function fgets;
use function fsockopen;
use function fwrite;
use function is_array;
use function json_decode;
use function sprintf;
use function stream_set_timeout;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Implements the query protocol for Mumble voice communication servers.
 * Retrieves server information, user lists, channel details, and connection statistics.
 */
class Mumble extends CSQuery implements ProtocolInterface
{
    private const PORT_DIFF      = -36938; // clientPort + PORT_DIFF = query port (64738 + -36938 = 27800)
    public string $name          = 'Mumble';
    public array $supportedGames = ['Mumble'];
    public string $protocol      = 'mumble';

    /**
     * Default client (voice) port for Mumble is 64738.
     */
    public int $clientPort = 64738;

    /**
     * Channel list parsed from Murmur JSON.
     *
     * @var array<mixed>
     */
    public array $channels = [];

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct($address, $queryport);

        // Ensure address is a string (CSQuery::$address is non-nullable)
        if ($address !== null) {
            $this->address = $address;
        }

        // Default query port for Murmur is 27800 when none provided
        if ($queryport === null) {
            $this->queryport = 27800;
        }
    }

    /**
     * query method.
     */
    #[Override]
    public function query(ServerAddress $addr): ServerInfo
    {
        $info         = new ServerInfo;
        $info->online = false;

        // Determine the query port. If caller supplied a client port (e.g. 64738), map to query port
        if ($addr->port !== 0) {
            if ($addr->port >= 60000) {
                // assume this is the client port; map to query port
                $queryPort = $addr->port + self::PORT_DIFF;
            } else {
                $queryPort = $addr->port;
            }
        } else {
            // fallback to configured queryport or default 27800
            $queryPort = $this->queryport ?? 27800;
        }

        // try a TCP connect to the query port; Murmur responds with a JSON payload to the 'json' packet
        $fp = @fsockopen($addr->ip, $queryPort, $errno, $errstr, 5);

        if ($fp === false) {
            $this->errstr = sprintf('connect failed: %s (%d)', $errstr !== '' && $errstr !== '0' ? $errstr : 'unknown', $errno !== 0 ? $errno : 0);

            return $info;
        }

        stream_set_timeout($fp, 4);

        // Send the 'json' packet (4 ASCII bytes) to request JSON status
        @fwrite($fp, 'json');

        // Read entire response
        $buffer = '';

        while (!feof($fp)) {
            $chunk = @fgets($fp, 8192);

            if ($chunk === false) {
                break;
            }
            $buffer .= $chunk;
        }

        fclose($fp);

        if ($buffer === '') {
            $this->errstr = 'no response from murmur query port';

            return $info;
        }

        $data = @json_decode($buffer, true);

        if (!is_array($data)) {
            $this->errstr = 'unable to decode murmur JSON response';

            return $info;
        }

        // Determine server title from common keys
        $this->servertitle = (string) ($data['name'] ?? $data['hostname'] ?? $data['x_connecturl'] ?? ($addr->ip . ':' . $queryPort));

        // Extract players and channels
        $channels = [];
        $players  = [];

        $extract = static function (array &$node, ?int $parentId = null) use (&$extract, &$channels, &$players): void
        {
            // If node contains 'id' and 'name' treat it as a channel
            if (isset($node['id'], $node['name'])) {
                $cid            = $node['id'];
                $channels[$cid] = [
                    'id'     => $cid,
                    'name'   => $node['name'] ?? 'unknown',
                    'parent' => $node['parent'] ?? $parentId,
                ];

                // collect any users in this channel
                if (is_array($node['users'] ?? null) && $node['users'] !== []) {
                    foreach ($node['users'] as $user) {
                        // user might be keyed by session id
                        if (is_array($user)) {
                            $pname     = $user['name'] ?? $user['username'] ?? null;
                            $pid       = $user['userid'] ?? $user['session'] ?? null;
                            $players[] = [
                                'name'    => $pname ?? 'unknown',
                                'id'      => $pid,
                                'channel' => $cid,
                            ];
                        }
                    }
                }

                // recurse channels
                if (is_array($node['channels'] ?? null) && $node['channels'] !== []) {
                    foreach ($node['channels'] as $child) {
                        if (is_array($child)) {
                            $extract($child, $cid);
                        }
                    }
                }
            } else {
                // if node is an associative list of channels
                foreach ($node as $v) {
                    if (is_array($v) && (isset($v['id']) || isset($v['name']) || isset($v['users']))) {
                        $extract($v, $parentId);
                    }
                }
            }
        };

        // Murmur usually encloses channels/users under 'root'
        if (isset($data['root'])) {
            $extract($data['root']);
        } else {
            $extract($data);
        }

        // Fallback: some providers include players at top-level 'users'
        if ($players === [] && isset($data['users']) && is_array($data['users'])) {
            foreach ($data['users'] as $u) {
                $players[] = [
                    'name'    => $u['name'] ?? 'unknown',
                    'id'      => $u['userid'] ?? $u['session'] ?? null,
                    'channel' => $u['channel'] ?? 0,
                ];
            }
        }

        // set info
        $this->numplayers = count($players);
        $this->maxplayers = isset($data['x_gtmurmur_max_users']) ? (int) $data['x_gtmurmur_max_users'] : ($this->maxplayers ?? 0);
        $this->players    = $players;
        $this->channels   = array_values($channels);
        $this->online     = true;

        $info->online      = true;
        $info->servertitle = $this->servertitle;
        $info->numplayers  = $this->numplayers;
        $info->maxplayers  = $this->maxplayers;
        $info->players     = $this->players;
        $info->channels    = $this->channels;

        return $info;
    }

    /**
     * query_server method.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        $addr = new ServerAddress($this->address ?? '', $this->queryport ?? $this->clientPort);
        $info = $this->query($addr);

        $this->online      = $info->online;
        $this->servertitle = $info->servertitle ?? '';
        $this->numplayers  = $info->numplayers ?? 0;
        $this->maxplayers  = $info->maxplayers ?? 0;
        $this->players     = $info->players ?? [];

        return $this->online;
    }

    /**
     * getProtocolName method.
     */
    #[Override]
    public function getProtocolName(): string
    {
        return $this->protocol;
    }

    /**
     * getVersion method.
     */
    #[Override]
    public function getVersion(ServerInfo $info): string
    {
        return $info->gameversion ?? 'unknown';
    }
}
