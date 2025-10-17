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
 * Call of Duty: Black Ops Mac protocol implementation.
 *
 * Mac version of Call of Duty: Black Ops, uses Quake3 protocol.
 */
class Blackopsmac extends Quake3Arena
{
    /**
     * Protocol name.
     */
    public string $name = 'Call of Duty: Black Ops Mac';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Call of Duty: Black Ops Mac'];

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
