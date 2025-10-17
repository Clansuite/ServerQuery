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

use function explode;
use function fclose;
use function fgets;
use function fsockopen;
use function fwrite;
use function is_array;
use function preg_split;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function stream_set_timeout;
use function trim;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Queries TeamSpeak 3 voice communication servers.
 *
 * Retrieves server information including channels, clients, server settings, and permissions
 * by connecting to the query port and parsing the text-based response protocol.
 * Enables monitoring of TeamSpeak 3 server status and user activity.
 */
class Teamspeak3 extends CSQuery implements ProtocolInterface
{
    public string $name = 'Teamspeak 3';

    /** @var array<string> */
    public array $supportedGames = ['Teamspeak 3'];
    public string $protocol      = 'teamspeak3';

    /**
     * Client (voice) port to select virtual server (default 9987).
     */
    public int $clientPort = 9987;

    /**
     * Constructor.
     *
     * Initializes the TeamSpeak 3 query instance.
     *
     * @param null|string $address   The server address to query
     * @param null|int    $queryport The query port for the TeamSpeak 3 server (default 10011)
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct();
        $this->address   = $address;
        $this->queryport = $queryport ?? 10011;
        // default client (voice) port to select the virtual server
        $this->clientPort = 9987;
    }

    /**
     * Performs a query on the specified TeamSpeak 3 server address.
     *
     * Connects to the query port, retrieves server information, and returns a ServerInfo object
     * containing the query results including channels and clients.
     *
     * @param ServerAddress $addr The server address and query port to connect to
     *
     * @return ServerInfo Server information including status, channels, and clients
     */
    #[Override]
    public function query(ServerAddress $addr): ServerInfo
    {
        $info         = new ServerInfo;
        $info->online = false;

        $port = $addr->port !== 0 ? $addr->port : 10011;

        $this->debug[] = sprintf('-> connect %s:%d', $addr->ip, $port);

        $host   = $addr->ip;
        $errno  = 0;
        $errstr = '';
        // Call fsockopen with reference params
        $fp = @fsockopen($host, $port, $errno, $errstr);

        if ($fp === false) {
            // normalize error display values
            $errstr_display = $errstr !== '' && $errstr !== '0' ? $errstr : 'unknown';
            $errno_display  = $errno !== 0 ? $errno : 0;
            $this->errstr   = sprintf('connect failed: %s (%d)', $errstr_display, $errno_display);
            $this->debug[]  = sprintf('<- connect failed: %s (%d)', $errstr_display, $errno_display);

            return $info;
        }

        // ensure we don't block forever
        stream_set_timeout($fp, 5);
        $banner = fgets($fp, 4096);

        if ($banner === false) {
            $banner = '';
        }
        $this->debug[] = '<- banner: ' . trim($banner);

        // select virtual server by client port first
        $this->debug[] = sprintf('-> use port=%d', $this->clientPort);
        fwrite($fp, sprintf("use port=%d\n", $this->clientPort));
        $useOk = false;

        while (($line = fgets($fp, 4096)) !== false) {
            $line          = trim($line);
            $this->debug[] = '<- ' . $line;

            if ($line === 'error id=0 msg=ok') {
                $useOk = true;

                break;
            }

            if (str_starts_with($line, 'error id=')) {
                // permission or server ID errors
                if (str_contains($line, 'insufficient')) {
                    $this->errstr = 'insufficient client permissions for query';
                } elseif (str_contains($line, 'invalid')) {
                    $this->errstr = 'invalid server id / no selected virtual server';
                } else {
                    $this->errstr = $line;
                }
                // stop early
                fclose($fp);
                $this->debug[] = '-> close';

                return $info;
            }
        }

        // send serverinfo
        $this->debug[] = '-> serverinfo';
        fwrite($fp, "serverinfo\n");
        $details = '';

        while (($line = fgets($fp, 4096)) !== false) {
            $line          = trim($line);
            $this->debug[] = '<- ' . $line;

            if ($line === 'error id=0 msg=ok') {
                break;
            }
            $details .= $line . "\n";
        }

        // send clientlist
        $this->debug[] = '-> clientlist';
        fwrite($fp, "clientlist\n");
        $clientsRaw = '';

        while (($line = fgets($fp, 4096)) !== false) {
            $line          = trim($line);
            $this->debug[] = '<- ' . $line;

            if ($line === 'error id=0 msg=ok') {
                break;
            }
            $clientsRaw .= $line . "\n";
        }

        fclose($fp);
        $this->debug[] = '-> close';

        // Parse details (space separated key=value)
        $props = $this->parseProperties($details);

        $info->online      = true;
        $info->servertitle = (string) ($props['virtualserver_name'] ?? '');
        $info->gameversion = (string) ($props['virtualserver_version'] ?? '');
        $info->numplayers  = isset($props['virtualserver_clientsonline']) ? (int) $props['virtualserver_clientsonline'] - (int) ($props['virtualserver_queryclientsonline'] ?? 0) : 0;
        $info->maxplayers  = isset($props['virtualserver_maxclients']) ? (int) $props['virtualserver_maxclients'] : 0;
        $info->mapname     = '';

        // Parse clients
        $info->players = [];
        $clients       = $this->parseClientList($clientsRaw);

        foreach ($clients as $client) {
            if (is_array($client)) {
                $info->players[] = [
                    'name' => (string) ($client['client_nickname'] ?? ''),
                    'id'   => (string) ($client['clid'] ?? ''),
                    'team' => (string) ($client['cid'] ?? ''),
                ];
            }
        }

        return $info;
    }

    /**
     * Returns the protocol name for TeamSpeak 3.
     *
     * @return string The protocol identifier 'teamspeak3'
     */
    #[Override]
    public function getProtocolName(): string
    {
        return $this->protocol;
    }

    /**
     * Extracts the TeamSpeak 3 server version from server information.
     *
     * @param ServerInfo $info The server information object
     *
     * @return string The server version string, or 'unknown' if not available
     */
    #[Override]
    public function getVersion(ServerInfo $info): string
    {
        return $info->gameversion ?? 'unknown';
    }

    /**
     * Queries the TeamSpeak 3 server and populates server information.
     *
     * Uses the query method to retrieve server data and updates internal state
     * with server details, channels, and client information.
     *
     * @param bool $getPlayers Whether to retrieve client information
     * @param bool $getRules   Whether to retrieve server rules/settings
     *
     * @return bool True on successful query, false on failure
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        $addr = new ServerAddress($this->address ?? '', $this->queryport ?? 10011);
        $info = $this->query($addr);

        $this->online      = $info->online;
        $this->servertitle = $info->servertitle ?? '';
        $this->mapname     = $info->mapname ?? '';
        $this->numplayers  = $info->numplayers;
        $this->maxplayers  = $info->maxplayers;
        $this->players     = [];

        foreach ($info->players as $p) {
            $this->players[] = [
                'name' => $p['name'] ?? '',
                'id'   => $p['id'] ?? '',
                'team' => $p['team'] ?? '',
            ];
        }

        return $this->online;
    }

    /**
     * Parses a TeamSpeak 3 server info line into key-value pairs.
     *
     * @return array<mixed>
     */
    private function parseProperties(string $data): array
    {
        $props = [];

        $split = preg_split('/\n/', trim($data));
        $lines = $split !== false ? $split : [];

        foreach ($lines as $line) {
            $items = preg_split('/\s+/', trim($line));
            $items = $items !== false ? $items : [];

            foreach ($items as $item) {
                if ($item === '') {
                    continue;
                }
                $kv = explode('=', $item, 2);
                $k  = $kv[0] ?? '';
                $v  = $kv[1] ?? '';
                // unescape TeamSpeak ServerQuery escaped sequences
                // \s -> space, \p -> pipe, \/ -> /, \\ -> \\, \n -> newline
                $v         = str_replace(['\\s', '\\p', '\\/', '\\\\', '\\n'], [' ', '|', '/', '\\', "\n"], $v);
                $props[$k] = $v;
            }
        }

        return $props;
    }

    /**
     * @return array<mixed>
     */
    private function parseClientList(string $data): array
    {
        $out = [];

        $split = preg_split('/\n/', trim($data));
        $lines = $split !== false ? $split : [];

        foreach ($lines as $line) {
            $split2 = preg_split('/\|/', trim($line));
            $items  = $split2 !== false ? $split2 : [];

            foreach ($items as $item) {
                if ($item === '') {
                    continue;
                }
                $pairs = preg_split('/\s+/', trim($item));
                $pairs = $pairs !== false ? $pairs : [];
                $props = [];

                foreach ($pairs as $pair) {
                    $kv = explode('=', $pair, 2);
                    $k  = $kv[0] ?? '';
                    $v  = $kv[1] ?? '';
                    // same unescaping for client values
                    $v = str_replace(['\\s', '\\p', '\\/', '\\\\', '\\n'], [' ', '|', '/', '\\', "\n"], $v);

                    if ($k !== '') {
                        $props[$k] = $v;
                    }
                }

                if ($props !== []) {
                    $out[] = $props;
                }
            }
        }

        return $out;
    }
}
