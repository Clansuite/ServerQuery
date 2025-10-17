<?php

declare(strict_types=1);

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

use Override;

/**
 * Left 4 Dead protocol implementation.
 *
 * Based on Steam/Source protocol.
 */
class L4d extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'Left 4 Dead';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Left 4 Dead'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Left 4 Dead'];

    /**
     * Left 4 Dead uses standard Source engine protocol.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        // Use parent implementation
        return parent::query_server($getPlayers, $getRules);
    }
}
