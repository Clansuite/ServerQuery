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
 * Zandronum / Skulltag Server Query Protocol.
 *
 * - Implements the Launcher Protocol (v0.61)
 * - Sends a challenge packet
 * - Decompresses Huffman data
 * - Parses server + player info
 * - Backward compatible with Skulltag
 */
final class Zandronum extends LauncherProtocol
{
    /**
     * Protocol name.
     */
    public string $name = 'Zandronum';

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Zandronum';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Zandronum'];

    /**
     * Constructor.
     */
    public function __construct(string $address, int $queryport)
    {
        parent::__construct($address, $queryport);
        $this->gameName = 'Zandronum';
    }
}
