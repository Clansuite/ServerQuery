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

namespace Clansuite\Capture;

/**
 * Represents a game server address consisting of IP and port.
 */
final readonly class ServerAddress
{
    /**
     * Initializes the server address with the specified IP and port.
     *
     * @param string $ip   The server's IP address
     * @param int    $port The server's port number
     */
    public function __construct(
        public string $ip,
        public int $port
    ) {
    }
}
