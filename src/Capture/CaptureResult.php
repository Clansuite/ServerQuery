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
 * Represents the result of a server capture operation, containing raw packets and parsed server information.
 */
final class CaptureResult
{
    /**
     * Constructor.
     *
     * @param array<mixed> $rawPackets
     * @param array<mixed> $metadata
     */
    public function __construct(
        public array $rawPackets,
        public ServerInfo $serverInfo,
        public array $metadata
    ) {
    }
}
