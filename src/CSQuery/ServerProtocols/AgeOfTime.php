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
 * Age of Time protocol implementation.
 *
 * Age of Time uses the Torque Game Engine protocol (same as Tribes 2).
 */
class AgeOfTime extends Tribes2 implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Age of Time';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Age of Time'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'AgeOfTime';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Age of Time'];

    /**
     * Returns a native join URI for Age of Time.
     */
    #[Override]
    public function getNativeJoinURI(): string
    {
        // Age of Time uses ageoftime:// protocol for joining servers
        return 'ageoftime://' . $this->address . ':' . $this->hostport;
    }
}
