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
use Override;

/**
 * Killing Floor Protocol implementation.
 *
 * Extends Unreal2 protocol for Killing Floor specific features.
 */
class KillingFloor extends Unreal2 implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'KillingFloor';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['KillingFloor'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'killingfloor';

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        $address   = $address === null ? null : $address;
        $queryport = $queryport === null ? null : $queryport;
        parent::__construct($address, $queryport);

        // Killing Floor uses queryport + 1 for the game port
        if ($queryport !== null) {
            $this->hostport = $queryport + 1;
        }
    }

    /**
     * getProtocolName method.
     */
    #[Override]
    public function getProtocolName(): string
    {
        return $this->protocol;
    }
}
