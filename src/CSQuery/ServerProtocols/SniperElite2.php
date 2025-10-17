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
 * Sniper Elite V2 Protocol implementation.
 *
 * Sniper Elite V2 uses the Unreal Engine 2 protocol for server queries.
 *
 * @deprecated This is a legacy protocol. Sniper Elite V2 multiplayer servers
 * have been shut down by the publisher and are no longer operational.
 * No active servers exist for this game. Research shows that servers
 * appearing as "Sniper Elite V2" on GameTracker are actually Sniper Elite 4
 * servers using Source protocol (not Unreal2).
 */
class SniperElite2 extends Unreal2
{
    /**
     * Protocol name.
     */
    public string $name = 'SniperElite2';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Sniper Elite V2'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'sniperelite2';

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct($address, $queryport);
    }
}
