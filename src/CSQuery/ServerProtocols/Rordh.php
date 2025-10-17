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
 * Darkest Hour protocol implementation.
 *
 * Darkest Hour uses the Unreal2 protocol.
 */
class Rordh extends Unreal2
{
    /**
     * Protocol name.
     */
    public string $name = 'Darkest Hour';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Darkest Hour'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Unreal2';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Darkest Hour'];

    /**
     * Port adjustment if needed (default 0).
     */
    protected int $port_diff = 0;
}
