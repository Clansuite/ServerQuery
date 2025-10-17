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
 * Call of Duty: Black Ops protocol implementation.
 *
 * Extends Quake3Arena protocol.
 */
class Blackops extends Quake3Arena
{
    /**
     * Protocol name.
     */
    public string $name = 'Call of Duty: Black Ops';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Call of Duty: Black Ops'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Quake3';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Call of Duty'];
}
