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
 * Handles the query protocol for Counter-Strike: Global Offensive game servers.
 *
 * Extends the base Steam protocol functionality to provide CS:GO specific
 * server querying capabilities, retrieving information about matches, players, and settings.
 */
class Csgo extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'Counter-Strike: Global Offensive';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Counter-Strike: Global Offensive'];

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
     * CS:GO specific port adjustment if needed.
     */
    protected int $port_diff = 0;

    /**
     * Returns a native join URI for CS:GO.
     */
    #[Override]
    public function getNativeJoinURI(): string
    {
        return 'steam://connect/' . $this->address . ':' . $this->hostport;
    }

    /**
     * query_server method.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        // CS:GO uses standard Source engine protocol, so we can use parent implementation
        return parent::query_server($getPlayers, $getRules);
    }
}
