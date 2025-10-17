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

use Clansuite\Capture\CaptureResult;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;

interface CaptureStrategyInterface
{
    /**
     * @param array<mixed> $options
     */
    public function capture(ProtocolInterface $protocol, ServerAddress $addr, array $options): CaptureResult;
}
