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
 * PixARK protocol implementation.
 */
class Pixark extends ArkSurvivalEvolved
{
    /**
     * Protocol name.
     */
    public string $name = 'PixARK';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['PixARK'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['PixARK'];
    protected int $port_diff       = 1;

    /**
     * Whether to auto-calculate query port from game port.
     */
    protected bool $autoCalculateQueryPort = true;
}
