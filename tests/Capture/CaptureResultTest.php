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

use Clansuite\Capture\CaptureResult;
use Clansuite\Capture\ServerInfo;
use PHPUnit\Framework\TestCase;

final class CaptureResultTest extends TestCase
{
    public function testConstruct(): void
    {
        $si = new ServerInfo;
        $cr = new CaptureResult(['p'], $si, ['m' => 1]);

        $this->assertSame(['p'], $cr->rawPackets);
        $this->assertSame($si, $cr->serverInfo);
        $this->assertSame(['m' => 1], $cr->metadata);
    }
}
