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

use function array_shift;
use function array_slice;
use function count;
use function explode;
use function is_string;
use function preg_match;
use function trim;
use Override;

/**
 * Implements the query protocol for Quake 2 servers.
 * Extends the base Quake protocol with Quake 2 specific query handling and data parsing.
 */
class Quake2 extends Quake
{
    /**
     * Protocol name.
     */
    public string $name = 'Quake 2';

    /**
     * List of supported games.
     */
    public array $supportedGames = ['Quake 2'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Quake';

    /**
     * Game series.
     */
    public string $game_series = 'Quake';

    /**
     * query_server method.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        if ($this->online) {
            $this->reset();
        }

        // Quake2 uses simple text commands - try different ones
        $commands = ["status\n", "info\n", "ping\n"];

        $result = false;

        foreach ($commands as $command) {
            if (($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $command)) !== false) {
                break;
            }
        }

        if ($result === '' || $result === '0' || $result === false) {
            $this->errstr = 'No reply received';

            return false;
        }

        // Parse the Quake2 response
        // Format: first line is server info, subsequent lines are players
        $lines = explode("\n", trim($result));

        // First line contains server info in key\value format
        $serverInfo = $lines[0];
        $this->parseServerInfo($serverInfo);

        // Remaining lines are players (if any)
        if ($getPlayers && count($lines) > 1) {
            $this->parsePlayers(array_slice($lines, 1));
        }

        $this->online = true;

        return true;
    }

    /**
     * Parse server info line.
     */
    private function parseServerInfo(string $info): void
    {
        // Format: \key\value\key\value...
        $parts = explode('\\', $info);

        // Skip empty first part if it starts with \
        if (isset($parts[0]) && $parts[0] === '') {
            array_shift($parts);
        }

        $rules = [];

        for ($i = 0; $i < count($parts) - 1; $i += 2) {
            $key   = $parts[$i] ?? '';
            $value = $parts[$i + 1] ?? '';

            // Map common keys to properties
            switch ($key) {
                case 'hostname':
                    $this->servertitle = $value;

                    break;

                case 'mapname':
                    $this->mapname = $value;

                    break;

                case 'maxclients':
                    $this->maxplayers = (int) $value;

                    break;

                case 'version':
                    $this->gameversion = $value;

                    break;

                case 'gamename':
                    $this->gamename = $value;

                    break;
            }

            $rules[$key] = $value;
        }

        $this->rules    = $rules;
        $this->gametype = $rules['gametype'] ?? '';
    }

    /**
     * Parse player lines.
     *
     * @param array<mixed> $playerLines
     */
    private function parsePlayers(array $playerLines): void
    {
        $players = [];

        foreach ($playerLines as $line) {
            if (!is_string($line)) {
                continue;
            }
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if ($line === '0') {
                continue;
            }

            // Quake2 player format: score ping "name"
            // Example:  5  50 "PlayerName"
            if (preg_match('/^\s*(\d+)\s+(\d+)\s+"([^"]*)"/', $line, $matches) === 1) {
                $players[] = [
                    'score' => (int) $matches[1],
                    'ping'  => (int) $matches[2],
                    'name'  => $matches[3],
                ];
            }
        }

        $this->numplayers = count($players);
        $this->playerkeys = ['name' => true, 'score' => true, 'ping' => true];
        $this->players    = $players;
    }
}
