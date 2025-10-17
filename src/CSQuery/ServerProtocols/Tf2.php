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
 * Team Fortress 2 protocol implementation.
 *
 * Based on Steam/Source protocol.
 */
class Tf2 extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'Team Fortress 2';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Team Fortress 2'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Team Fortress'];

    /**
     * Team Fortress 2 uses standard Source engine protocol.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        // Use parent implementation but disable players due to parsing issues
        return parent::query_server(false, $getRules);
    }
}
