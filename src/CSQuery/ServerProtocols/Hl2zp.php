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
 * Zombie Panic! Source protocol implementation.
 *
 * Based on Steam/Source protocol.
 */
class Hl2zp extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'Zombie Panic! Source';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Zombie Panic! Source'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Zombie Panic! Source uses standard Source engine protocol.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        // Use parent implementation
        return parent::query_server($getPlayers, $getRules);
    }
}
