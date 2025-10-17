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

use Override;

/**
 * Implements the query protocol for Frontlines: Fuel of War game servers.
 * Uses the Steam query protocol to retrieve server information and player data.
 */
class Ffow extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'Frontlines Fuel of War';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Frontlines Fuel of War'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'ffow';

    /**
     * getProtocolName method.
     */
    #[Override]
    public function getProtocolName(): string
    {
        return $this->protocol;
    }
}
