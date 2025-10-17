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
 * Unreal Tournament protocol implementation.
 *
 * Unreal Tournament uses the Gamespy protocol.
 */
class Ut extends Gamespy
{
    /**
     * Protocol name.
     */
    public string $name = 'Unreal Tournament';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Unreal Tournament'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Gamespy';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Unreal Tournament'];

    /**
     * Port adjustment if needed (default 0).
     */
    protected int $port_diff = 1;
}
