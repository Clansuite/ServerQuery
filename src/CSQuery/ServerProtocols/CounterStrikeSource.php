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

use Override;

/**
 * Counter-Strike: Source protocol implementation.
 *
 * The Source engine uses the same A2S (Source) query protocol implemented
 * in the `Steam` base class. This class exists to provide a distinct
 * protocol entry for Counter-Strike: Source and to allow game-specific
 * customizations in the future.
 */
class CounterStrikeSource extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'Counter-Strike: Source';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Counter-Strike: Source'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Counter-Strike'];

    /**
     * CS:S specific port adjustment if needed (default 0).
     */
    protected int $port_diff = 0;

    /**
     * Returns a native join URI for Source games.
     */
    #[Override]
    public function getNativeJoinURI(): string
    {
        return 'steam://connect/' . $this->address . ':' . $this->hostport;
    }

    /**
     * Query server - delegate to Steam implementation (Source protocol).
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        return parent::query_server($getPlayers, $getRules);
    }
}
