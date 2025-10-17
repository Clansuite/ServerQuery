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
 * Unreal Tournament 2004 protocol implementation.
 *
 * Unreal Tournament 2004 uses the Unreal2 protocol.
 */
class Ut2k4 extends Unreal2
{
    /**
     * Protocol name.
     */
    public string $name = 'Unreal Tournament 2004';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Unreal Tournament 2004'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Unreal2';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Unreal Tournament 2004'];

    /**
     * Port adjustment if needed (default 0).
     */
    protected int $port_diff = 0;
}
