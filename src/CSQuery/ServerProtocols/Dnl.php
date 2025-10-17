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
 * Dark and Light protocol implementation.
 *
 * Dark and Light uses the Unreal2 protocol.
 */
class Dnl extends Unreal2
{
    /**
     * Protocol name.
     */
    public string $name = 'Dark and Light';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Dark and Light'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Unreal2';

    /**
     * Game series.
     */
    public array $game_series_list = ['Dark and Light'];

    /**
     * Port adjustment if needed (default 0).
     */
    protected int $port_diff = 0;
}
