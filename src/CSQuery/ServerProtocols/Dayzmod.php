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
 * DayZ Mod protocol implementation.
 *
 * DayZ Mod uses the Gamespy2 protocol.
 */
class Dayzmod extends Gamespy2
{
    /**
     * Protocol name.
     */
    public string $name = 'DayZ Mod';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['DayZ Mod'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Gamespy2';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['DayZ Mod'];

    /**
     * Port adjustment if needed (default 0).
     */
    protected int $port_diff = 0;
}
