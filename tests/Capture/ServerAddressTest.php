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

use Clansuite\Capture\ServerAddress;
use PHPUnit\Framework\TestCase;

final class ServerAddressTest extends TestCase
{
    public function testConstructAndProperties(): void
    {
        $addr = new ServerAddress('127.0.0.1', 27015);

        $this->assertSame('127.0.0.1', $addr->ip);
        $this->assertSame(27015, $addr->port);
    }
}
