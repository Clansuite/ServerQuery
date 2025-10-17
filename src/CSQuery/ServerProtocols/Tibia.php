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

use function fclose;
use function fread;
use function fsockopen;
use function fwrite;
use function is_array;
use function simplexml_load_string;
use function str_starts_with;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Tibia protocol implementation.
 *
 * Uses TCP protocol with XML response.
 */
class Tibia extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Tibia';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Tibia'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'tibia';

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct($address, $queryport);
    }

    /**
     * query method.
     */
    #[Override]
    public function query(ServerAddress $addr): ServerInfo
    {
        $info         = new ServerInfo;
        $info->online = false;

        $socket = @fsockopen($addr->ip, $addr->port, $errno, $errstr, 5);

        if ($socket === false) {
            return $info;
        }

        // Send info packet
        $packet = "\x06\x00\xFF\xFF\x69\x6E\x66\x6F";
        fwrite($socket, $packet);

        // Read response
        $response = fread($socket, 4096);
        fclose($socket);

        if ($response !== false) {
            $this->parseResponse($response, $info);
            $info->online = true;
        }

        return $info;
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
        return $info->version ?? 'unknown';
    }

    /**
     * query_server method.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        $addr = new ServerAddress($this->address ?? '', $this->queryport ?? 0);
        $info = $this->query($addr);

        $this->online      = $info->online;
        $this->servertitle = $info->name ?? '';
        $this->mapname     = $info->map ?? '';
        $this->numplayers  = $info->players_current ?? 0;
        $this->maxplayers  = $info->players_max ?? 0;
        $this->players     = [];

        foreach ($info->players as $player) {
            if (!is_array($player)) {
                continue;
            }
            $this->players[] = [
                'name'  => $player['name'] ?? '',
                'score' => $player['score'] ?? 0,
                'time'  => $player['time'] ?? 0,
            ];
        }

        return $this->online;
    }

    private function parseResponse(string $response, ServerInfo $info): void
    {
        $xmlDoc = @simplexml_load_string($response);

        if ($xmlDoc === false) {
            return;
        }

        // Parse serverinfo
        $attributes            = $xmlDoc->serverinfo->attributes();
        $info->name            = (string) ($attributes['servername'] ?? '');
        $info->map             = (string) ($attributes['map_name'] ?? '');
        $info->players_current = (int) ($attributes['players_online'] ?? 0);
        $info->players_max     = (int) ($attributes['players_max'] ?? 0);
        $info->version         = (string) ($attributes['server'] ?? '');

        // Parse MOTD
        $info->motd = (string) $xmlDoc->motd;

        // Parse players
        $info->players = [];
        $attributes    = $xmlDoc->players->attributes();

        if ($attributes !== null) {
            foreach ($attributes as $key => $value) {
                // Players are stored as attributes, parse them
                if (str_starts_with($key, 'player')) {
                    $info->players[] = [
                        'name'  => (string) $value,
                        'score' => 0,
                        'time'  => 0,
                    ];
                }
            }
        }
    }
}
