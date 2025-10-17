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
 * Brink protocol implementation.
 *
 * Brink uses the Steam (A2S) protocol with port_diff = 1.
 */
class Brink extends Steam implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Brink';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Brink'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Brink'];

    /**
     * Port adjustment if needed (default 0).
     */
    protected int $port_diff = 1;

    /**
     * Returns a native join URI for Brink.
     */
    #[Override]
    public function getNativeJoinURI(): string
    {
        return 'steam://connect/' . $this->address . ':' . $this->hostport;
    }
}
