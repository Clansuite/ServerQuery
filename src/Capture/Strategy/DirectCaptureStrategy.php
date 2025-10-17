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

namespace Clansuite\Capture\Strategy;

use function time;
use Clansuite\Capture\CaptureResult;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Override;

/**
 * Capture strategy that performs server queries directly without using worker processes.
 */
final class DirectCaptureStrategy implements CaptureStrategyInterface
{
    /**
     * capture method.
     *
     * @param array<mixed> $options
     */
    #[Override]
    public function capture(ProtocolInterface $protocol, ServerAddress $addr, array $options): CaptureResult
    {
        // Stub: query and return result without actual capture
        $serverInfo = $protocol->query($addr);
        $rawPackets = []; // TODO: capture packets
        $metadata   = [
            'ip'        => $addr->ip,
            'port'      => $addr->port,
            'protocol'  => $protocol->getProtocolName(),
            'timestamp' => time(),
        ];

        return new CaptureResult($rawPackets, $serverInfo, $metadata);
    }
}
