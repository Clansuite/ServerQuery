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
 * Don't Starve Together protocol implementation.
 *
 * DST uses the Steam A2S query protocol.
 */
class DontStarveTogether extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'Don\'t Starve Together';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Don\'t Starve Together'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['DST'];

    /**
     * Port adjustment if needed (default 0).
     */
    protected int $port_diff = 0;
}
