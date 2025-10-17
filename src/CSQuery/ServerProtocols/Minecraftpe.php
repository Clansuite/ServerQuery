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
 * Minecraft Pocket Edition protocol implementation.
 *
 * Minecraft Pocket Edition uses the Minecraft protocol with different protocol version.
 */
class Minecraftpe extends Minecraft
{
    /**
     * Protocol name.
     */
    public string $name = 'Minecraft Pocket Edition';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Minecraft Pocket Edition'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'minecraft';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Minecraft Pocket Edition'];

    /**
     * Port adjustment if needed (default 0).
     */
    protected int $port_diff = 0;

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null, string $protocolVersion = 'slp')
    {
        parent::__construct($address, $queryport, $protocolVersion);
    }
}
