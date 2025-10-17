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

/**
 * Implements the query protocol for Miscreated game servers.
 * Utilizes the Steam query protocol to retrieve server information, player lists, and game statistics.
 */
class Miscreated extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'Miscreated';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Miscreated'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Miscreated'];
    protected int $port_diff       = 2;

    /**
     * Whether to auto-calculate query port from game port.
     */
    protected bool $autoCalculateQueryPort = true;
}
