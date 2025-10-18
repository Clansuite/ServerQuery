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

/**
 *  Abstract class that implements quake related stuff.
 *
 * Implements everything that all quake protocols have in common
 */

namespace Clansuite\ServerQuery\ServerProtocols;

use const PREG_SPLIT_NO_EMPTY;
use function array_shift;
use function array_slice;
use function count;
use function explode;
use function preg_split;
use function trim;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Provides the base query protocol implementation for Quake engine-based games.
 * Handles common query operations, data parsing, and structures shared across Quake variants.
 */
class Quake extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Quake';

    /**
     * List of supported games.
     */
    public array $supportedGames = ['Quake', 'Quake 2', 'Quake 3 Arena', 'Quake 4'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Quake';

    /**
     * Game series.
     */
    public array $game_series_list = ['Quake'];

    /**
     * Constructor.
     *
     * Initializes the Quake protocol instance with server address and query port.
     *
     * @param null|string $address   Server IP address or hostname
     * @param null|int    $queryport Query port number
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct();
        $this->address   = $address;
        $this->queryport = $queryport;
    }

    /**
     * Queries the server for information, optionally including players and rules.
     *
     * @param bool $getPlayers Whether to retrieve the player list
     * @param bool $getRules   Whether to retrieve server rules
     *
     * @return bool True on successful query, false on failure
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        if ($this->online) {
            $this->reset();
        }

        $address = (string) $this->address;
        $port    = (int) $this->queryport;

        // Quake1 uses simple text commands - try different ones
        $commands = ["status\n", "info\n", "ping\n"];

        $result = false;

        foreach ($commands as $command) {
            if (($result = $this->sendCommand($address, $port, $command)) !== false) {
                break;
            }
        }

        if ($result === '' || $result === '0' || $result === false) {
            $this->errstr = 'No reply received';

            return false;
        }

        // Parse the Quake1 response
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
     *  Sends a rcon command to the game server.
     *
     * @param string $command  the command to send
     * @param string $rcon_pwd rcon password to authenticate with
     */
    public function rcon_query_server(string $command, string $rcon_pwd): false|string
    {
        $address = (string) $this->address;
        $port    = (int) $this->queryport;

        $command = "\xFF\xFF\xFF\xFF\x02rcon " . $rcon_pwd . ' ' . $command . "\x0a\x00";

        if (($result = $this->sendCommand($address, $port, $command)) === '' || ($result = $this->sendCommand($address, $port, $command)) === '0' || ($result = $this->sendCommand($address, $port, $command)) === false) {
            $this->errstr                            = 'Error sending rcon command';
            $this->debug['Command send ' . $command] = 'No reply received';

            return false;
        }

        return $result;
    }

    /**
     * query method.
     */
    #[Override]
    public function query(ServerAddress $addr): ServerInfo
    {
        $this->address   = $addr->ip;
        $this->queryport = $addr->port;
        $this->query_server(true, true);

        return new ServerInfo(
            address: $this->address,
            queryport: $this->queryport,
            online: $this->online,
            gamename: $this->gamename,
            gameversion: $this->gameversion,
            servertitle: $this->servertitle,
            mapname: $this->mapname,
            gametype: $this->gametype,
            numplayers: $this->numplayers,
            maxplayers: $this->maxplayers,
            rules: $this->rules,
            players: $this->players,
        );
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

    /**
     * Parse server info line.
     */
    private function parseServerInfo(string $info): void
    {
        // Format: \key\value\key\value...
        $parts = explode('\\', $info);

        // Skip empty first part if it starts with \\ (safe check)
        if (isset($parts[0]) && $parts[0] === '') {
            array_shift($parts);
        }

        $rules = [];

        for ($i = 0; $i < count($parts) - 1; $i += 2) {
            if (isset($parts[$i], $parts[$i + 1])) {
                $key   = $parts[$i];
                $value = $parts[$i + 1];

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

                    case 'clients':
                        $this->numplayers = (int) $value;

                        break;

                    case 'protocol':
                        // Protocol version
                        break;

                    case 'version':
                        $this->gameversion = $value;

                        break;

                    default:
                        // Store as rule
                        $rules[$key] = $value;

                        break;
                }
            }
        }

        $this->rules    = $rules;
        $this->gamename = 'Quake1';
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
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            if ($line === '0') {
                continue;
            }

            // Quake1 player format: score ping "name" "skin" team time
            // Example: 10 50 "PlayerName" "skin" 0 123.45
            $parts = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);

            if ($parts === false || count($parts) < 6) {
                continue;
            }

            $player = [
                'score' => (int) $parts[0],
                /** @phpstan-ignore offsetAccess.notFound */
                'ping' => (int) $parts[1],
                /** @phpstan-ignore offsetAccess.notFound */
                'name' => trim($parts[2], '"'),
                /** @phpstan-ignore offsetAccess.notFound */
                'skin' => trim($parts[3], '"'),
                /** @phpstan-ignore offsetAccess.notFound */
                'team' => (int) $parts[4],
                /** @phpstan-ignore offsetAccess.notFound */
                'time' => (float) $parts[5],
            ];

            $players[] = $player;
        }

        $this->players = $players;
    }
}
