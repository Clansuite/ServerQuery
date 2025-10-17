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
 * Medal of Honor Warfighter protocol implementation.
 *
 * Medal of Honor Warfighter uses the Battlefield 4 protocol.
 */
class Mohw extends Battlefield4
{
    /**
     * Protocol name.
     */
    public string $name = 'Medal of Honor Warfighter';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Medal of Honor Warfighter'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Battlefield4';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Medal of Honor Warfighter'];

    /**
     * Port adjustment if needed (default 0).
     */
    protected int $port_diff = 0;
}
