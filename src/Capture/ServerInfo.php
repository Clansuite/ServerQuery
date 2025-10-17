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

namespace Clansuite\Capture;

/**
 * Represents detailed information about a game server, including status, players, and configuration.
 */
final class ServerInfo
{
    /**
     * Initializes server information with the provided data.
     *
     * @param null|string  $address     Server IP address
     * @param null|int     $queryport   Query port number
     * @param bool         $online      Whether the server is online
     * @param null|string  $gamename    Name of the game
     * @param null|string  $gameversion Game version
     * @param null|string  $servertitle Server title
     * @param null|string  $mapname     Current map name
     * @param null|string  $gametype    Game type/mode
     * @param int          $numplayers  Current number of players
     * @param int          $maxplayers  Maximum number of players
     * @param array<mixed> $rules       Server rules/configuration
     * @param array<mixed> $players     List of players
     * @param array<mixed> $channels    Voice channels (for applicable games)
     * @param null|string  $errstr      Error message if query failed
     */
    public function __construct(
        public ?string $address = null,
        public ?int $queryport = null,
        public bool $online = false,
        public ?string $gamename = null,
        public ?string $gameversion = null,
        public ?string $servertitle = null,
        public ?string $mapname = null,
        public ?string $gametype = null,
        public int $numplayers = 0,
        public int $maxplayers = 0,
        public array $rules = [],
        public array $players = [],
        public array $channels = [],
        public ?string $errstr = null,
        public ?bool $password = null,
        public ?string $name = null,
        public ?string $map = null,
        public ?int $players_current = null,
        public ?int $players_max = null,
        public ?string $version = null,
        public ?string $motd = null,
    ) {
    }

    /**
     * Converts the server information to an associative array.
     *
     * @return array<mixed> Server info as key-value pairs
     */
    public function toArray(): array
    {
        return [
            'address'         => $this->address,
            'queryport'       => $this->queryport,
            'online'          => $this->online,
            'gamename'        => $this->gamename,
            'gameversion'     => $this->gameversion,
            'servertitle'     => $this->servertitle,
            'mapname'         => $this->mapname,
            'gametype'        => $this->gametype,
            'numplayers'      => $this->numplayers,
            'maxplayers'      => $this->maxplayers,
            'rules'           => $this->rules,
            'players'         => $this->players,
            'channels'        => $this->channels,
            'errstr'          => $this->errstr,
            'password'        => $this->password,
            'name'            => $this->name,
            'map'             => $this->map,
            'players_current' => $this->players_current,
            'players_max'     => $this->players_max,
            'version'         => $this->version,
            'motd'            => $this->motd,
        ];
    }
}
