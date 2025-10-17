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
 * Call of Duty: Black Ops 3 protocol implementation.
 *
 * Uses the Steam / A2S protocol (modern IW engine uses Valve-style queries in many deployments).
 */
class Codbo3 extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'Call of Duty: Black Ops 3';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Call of Duty: Black Ops 3'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Call of Duty'];
}
