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
 * Insurgency protocol implementation.
 *
 * Based on Steam/Source protocol.
 */
class Ins extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'Insurgency';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Insurgency'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Insurgency uses standard Source engine protocol.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        // Use parent implementation
        return parent::query_server($getPlayers, $getRules);
    }
}
