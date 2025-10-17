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
 * Halo protocol implementation.
 *
 * Extends GameSpy2 protocol.
 */
class Halo extends Gamespy2 implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Halo';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Halo'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'halo';

    /**
     * Constructor.
     */
    public function __construct(mixed $address = null, mixed $queryport = null)
    {
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
