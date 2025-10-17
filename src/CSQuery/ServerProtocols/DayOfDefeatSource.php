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

use function is_int;
use function is_string;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Override;

/**
 * Day of Defeat: Source protocol implementation.
 *
 * The Source engine uses the same A2S (Source) query protocol implemented
 * in the `Steam` base class. This class exists to provide a distinct
 * protocol entry for Day of Defeat: Source and to allow game-specific
 * customizations in the future.
 */
class DayOfDefeatSource extends Steam implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Day of Defeat: Source';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Day of Defeat: Source'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'dods';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Day of Defeat'];

    /**
     * DoD:S specific port adjustment if needed (default 0).
     */
    protected int $port_diff = 0;

    /**
     * Constructor.
     */
    public function __construct(mixed $address = null, mixed $queryport = null)
    {
        parent::__construct((is_string($address) ? $address : null), (is_int($queryport) ? $queryport : null));
    }

    /**
     * Returns a native join URI for Source games.
     */
    #[Override]
    public function getNativeJoinURI(): string
    {
        return 'steam://connect/' . $this->address . ':' . $this->hostport;
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
