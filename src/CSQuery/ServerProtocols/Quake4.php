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

use function count;
use function explode;
use function preg_match;
use function preg_split;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;
use Override;

/**
 * Queries Quake 4 game servers.
 *
 * Extends the base Quake protocol to handle Quake 4 specific server queries,
 * retrieving information about server status, players, and game settings.
 * Enables monitoring of Quake 4 multiplayer servers.
 */
class Quake4 extends Quake
{
    /**
     * Protocol name.
     */
    public string $name = 'Quake 4';

    /**
     * List of supported games.
     */
    public array $supportedGames = ['Quake 4'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Quake4';

    /**
     * Game series.
     */
    public array $game_series_list = ['Quake'];

    /**
     * query_server method.
     *
     * Queries the Quake 4 server and populates server information.
     *
     * Sends a Quake 4 specific query command and processes the response
     * to extract server details, player information, and game settings.
     *
     * @param bool $getPlayers Whether to retrieve player information
     * @param bool $getRules   Whether to retrieve server rules/settings
     *
     * @return bool True on successful query, false on failure
     */
    #[Override]
    public function query_server(mixed $getPlayers = true, mixed $getRules = true): bool
    {
        if ($this->online) {
            $this->reset();
        }

        $address = (string) $this->address;
        $port    = (int) $this->queryport;

        // Doom3/Quake4 protocol uses different packet format
        $command = "\xFF\xFFgetInfo\x00\x01\x00\x00\x00";

        if (($result = $this->sendCommand($address, $port, $command)) === '' || ($result = $this->sendCommand($address, $port, $command)) === '0' || ($result = $this->sendCommand($address, $port, $command)) === false) {
            $this->errstr = 'No reply received';

            return false;
        }

        // Parse the Doom3 packet format
        if (strlen($result) < 16) {
            $this->errstr = 'Invalid packet received';

            return false;
        }

        // Skip the packet header (16 bytes: short check, 12 bytes check2, int challengeId, int protocol, char pack)
        $data = substr($result, 16);

        // Parse server info
        $info = $this->parseDoom3Info($data);

        // Extract basic server information
        $this->gamename    = (string) ($info['gamename'] ?? 'quake4');
        $this->gameversion = $this->translateProtocolVersion((string) ($info['protocol'] ?? $info['si_version'] ?? ''));
        $this->servertitle = (string) ($info['si_name'] ?? '');
        $this->mapname     = (string) ($info['si_map'] ?? '');
        $this->gametype    = (string) ($info['si_gameType'] ?? '');
        $this->maxplayers  = (int) ($info['si_maxPlayers'] ?? 0);
        $this->numplayers  = 0; // Will be calculated from players

        // Store all rules
        $this->rules = $info;

        // Get players if requested
        if ($getPlayers && isset($info['si_players'])) {
            $this->parsePlayers((string) $info['si_players']);
        }

        $this->online = true;

        return true;
    }

    /**
     * Parse Doom3 server info string.
     *
     * @return array<string, mixed>
     */
    protected function parseDoom3Info(string $data): array
    {
        $info = [];

        // Remove the "infoResponse" header if present
        if (str_starts_with($data, 'infoResponse')) {
            $data = substr($data, 12); // Remove "infoResponse"
        }

        // The format is key\x00value\x00key\x00value\x00...
        // Split on null bytes
        $parts = explode("\x00", $data);

        // Skip empty parts at the beginning
        $i = 0;

        while ($i < count($parts) && ($parts[$i] ?? '') === '') {
            $i++;
        }

        // Parse key-value pairs
        // The format should be key\x00value\x00key\x00value\x00...
        while ($i < count($parts) - 1) {
            $potentialKey   = $parts[$i] ?? '';
            $potentialValue = $parts[$i + 1] ?? '';

            // Check if this looks like a key (contains known key patterns)
            $isKey = false;

            if (preg_match('/^(sv_|si_|fs_|net_|gamename|protocol)/', $potentialKey) === 1) {
                $isKey = true;
            }

            if ($isKey && $potentialKey !== '') {
                $info[$potentialKey] = $potentialValue;
                $i += 2; // Skip the value
            } else {
                // Not a key, skip this part
                $i++;
            }
        }

        return $info;
    }

    /**
     * Parse player information from si_players.
     *
     * @param string $playersData The si_players string containing player info
     */
    protected function parsePlayers(string $playersData): void
    {
        // si_players format: score ping name clan score ping name clan ...
        $parts   = preg_split('/\s+/', trim($playersData));
        $parts   = $parts !== false ? $parts : [];
        $players = [];

        for ($i = 0; $i < count($parts); $i += 4) {
            if ($i + 3 < count($parts)) {
                $score = (int) ($parts[$i] ?? 0);
                $ping  = (int) ($parts[$i + 1] ?? 0);
                $name  = $parts[$i + 2] ?? '';
                $clan  = $parts[$i + 3] ?? '';

                $players[] = [
                    'name'  => $name,
                    'score' => $score,
                    'ping'  => $ping,
                    'clan'  => $clan,
                ];
            }
        }

        $this->numplayers = count($players);
        $this->playerkeys = ['name' => true, 'score' => true, 'ping' => true, 'clan' => true];
        $this->players    = $players;
    }

    /**
     * Translate protocol version to human readable format.
     *
     * @param string $protocol The protocol version string
     *
     * @return string Human readable protocol version
     */
    protected function translateProtocolVersion(string $protocol): string
    {
        // If it's already a descriptive string, return it
        if (str_starts_with($protocol, 'Quake4') || str_starts_with($protocol, 'Q4')) {
            return $protocol;
        }

        $versions = [
            '2.62'    => 'Q4 1.0',
            '2.63'    => 'Q4 1.0 (German)',
            '2.66'    => 'Q4 Demo',
            '2.67'    => 'Q4 1.1 Beta',
            '2.68'    => 'Q4 1.1',
            '2.69'    => 'Q4 1.2',
            '2.71'    => 'Q4 1.3',
            '2.76'    => 'Q4 1.4 Beta',
            '2.77'    => 'Q4 1.4.1',
            '2.85'    => 'Q4 1.4.2',
            '2.77 DE' => 'Q4 1.4.1 (German)',
            '2.71 DE' => 'Q4 1.3 (German)',
            '2.85 DE' => 'Q4 1.4.2 (German)',
            '2.86'    => 'Q4 1.4.2 Demo',
        ];

        return $versions[$protocol] ?? $protocol;
    }
}
