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
 * Blockland protocol implementation.
 *
 * https://blockland.online/servers
 *
 * Blockland uses the Torque Game Engine protocol (same as Tribes 2).
 */
class Blockland extends Tribes2 implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Blockland';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Blockland'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Blockland';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Blockland'];

    /**
     * Returns a native join URI for Blockland.
     */
    #[Override]
    public function getNativeJoinURI(): string
    {
        // Blockland uses blockland:// protocol for joining servers
        return 'blockland://' . $this->address . ':' . $this->hostport;
    }
}
