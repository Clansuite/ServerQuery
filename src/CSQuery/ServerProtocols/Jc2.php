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
 * Just Cause 2 protocol implementation.
 *
 * Extends GameSpy3 protocol.
 */
class Jc2 extends Gamespy3 implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Jc2';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Just Cause 2 Multiplayer'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'gamespy3';

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
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
