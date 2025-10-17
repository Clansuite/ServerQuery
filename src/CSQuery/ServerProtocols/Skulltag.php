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
 * Skulltag Server Query Protocol.
 *
 * - Implements the Launcher Protocol (same as Zandronum)
 * - Sends a challenge packet
 * - Decompresses Huffman data
 * - Parses server + player info
 */
class Skulltag extends LauncherProtocol
{
    /**
     * Protocol name.
     */
    public string $name = 'Skulltag';

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Skulltag';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Skulltag'];

    /**
     * Constructor.
     */
    public function __construct(string $address, int $queryport)
    {
        parent::__construct($address, $queryport);
        $this->gameName = 'Skulltag';
    }
}
