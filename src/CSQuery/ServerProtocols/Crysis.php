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
 * Crysis protocol implementation.
 *
 * Crysis uses the Gamespy3 protocol.
 */
class Crysis extends Gamespy3
{
    /**
     * Protocol name.
     */
    public string $name = 'Crysis';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Crysis'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Gamespy3';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Crysis'];

    /**
     * Port adjustment if needed (default 0).
     */
    protected int $port_diff = 0;
}
