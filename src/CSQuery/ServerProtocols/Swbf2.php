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
 * Star Wars Battlefront 2 protocol implementation.
 *
 * SWBF2 uses the Battlefield 4 protocol.
 */
class Swbf2 extends Battlefield4
{
    /**
     * Protocol name.
     */
    public string $name = 'Star Wars Battlefront 2';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Star Wars Battlefront 2'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'BF4';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Star Wars Battlefront 2'];

    /**
     * Port adjustment if needed (default 0).
     */
    protected int $port_diff = 0;
}
