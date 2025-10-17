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

use function is_int;
use function is_string;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Override;

/**
 * Homefront protocol implementation.
 *
 * Extends Steam protocol.
 */
class Homefront extends Steam implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Homefront';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Homefront'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'source';

    /**
     * Constructor.
     */
    public function __construct(mixed $address = null, mixed $queryport = null)
    {
        $address   = $address === null ? null : (is_string($address) ? $address : null);
        $queryport = $queryport === null ? null : (is_int($queryport) ? $queryport : null);
        parent::__construct($address, $queryport);
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
