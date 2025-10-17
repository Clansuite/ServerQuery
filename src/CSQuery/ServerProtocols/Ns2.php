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
 * Natural Selection 2 protocol implementation.
 *
 * NS2 uses the Steam A2S query protocol.
 */
class Ns2 extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'Natural Selection 2';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Natural Selection 2'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Natural Selection 2'];

    /**
     * Port adjustment if needed (default 0).
     */
    protected int $port_diff = 1;
}
