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

use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Override;

/**
 * DayZ protocol implementation.
 *
 * Extends Steam protocol with DayZ specific port calculation.
 * Query port = 27016 + floor((host_port - 2302) / 100)
 */
class Dayz extends Steam implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'DayZ';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['DayZ'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'dayz';

    /**
     * Query server information.
     *
     * @return bool True on success, false on failure
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        $result = parent::query_server($getPlayers, $getRules);

        if ($result) {
            // For DayZ: query_port = game_port + 2714
            // So host_port = query_port - 2714
            $this->hostport = ($this->queryport ?? 0) - 2714;
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
            rules: $this->rules ?? [],
            players: $this->players ?? [],
            errstr: $this->errstr,
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
}
