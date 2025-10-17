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

use Clansuite\Capture\Protocol\ProtocolInterface;

/**
 * SQUAD protocol implementation.
 *
 * SQUAD uses the Steam A2S query protocol with a specific port offset.
 * Query port = client port + 19378
 */
class Squad extends Steam implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'SQUAD';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['SQUAD'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['SQUAD'];

    /**
     * Port adjustment: query_port = client_port + 19378.
     */
    protected int $port_diff = 19378;
}
