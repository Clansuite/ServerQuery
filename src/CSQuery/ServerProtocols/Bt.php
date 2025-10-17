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
 * Medal of Honor Breakthrough protocol implementation.
 *
 * Medal of Honor Breakthrough uses the Quake 3 Arena protocol.
 */
class Bt extends Quake3Arena
{
    /**
     * Protocol name.
     */
    public string $name = 'Medal of Honor Breakthrough';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Medal of Honor Breakthrough'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Quake3Arena';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Medal of Honor Breakthrough'];

    /**
     * Port adjustment if needed (default 0).
     */
    protected int $port_diff = 0;
}
