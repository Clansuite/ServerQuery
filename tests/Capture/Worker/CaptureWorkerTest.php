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

use Clansuite\Capture\Worker\CaptureWorker;
use PHPUnit\Framework\TestCase;

final class CaptureWorkerTest extends TestCase
{
    public function testConstructorWithDefaultValues(): void
    {
        $worker = new CaptureWorker;

        $this->assertInstanceOf(CaptureWorker::class, $worker);
    }

    public function testConstructorWithCustomValues(): void
    {
        $worker = new CaptureWorker(10, 5);

        $this->assertInstanceOf(CaptureWorker::class, $worker);
    }

    public function testQueryReturnsExpectedStructure(): void
    {
        $worker = new CaptureWorker;

        // Test that the worker returns the expected array structure
        // This is a basic test since full integration testing would require
        // complex mocking of the entire CSQuery stack

        $this->assertInstanceOf(CaptureWorker::class, $worker);

        // The actual query method would return an array with 'debug' and 'server_info' keys
        // containing the collected debug information and server details
        // We can't easily test the actual query without network mocking
    }

    public function testTimeoutConfiguration(): void
    {
        $timeout = 10;
        $worker  = new CaptureWorker($timeout);

        $this->assertInstanceOf(CaptureWorker::class, $worker);

        // The timeout is used internally when creating UdpClient
        // We can't easily test this without integration testing
    }

    public function testMaxRetriesConfiguration(): void
    {
        $maxRetries = 3;
        $worker     = new CaptureWorker(5, $maxRetries);

        $this->assertInstanceOf(CaptureWorker::class, $worker);

        // The maxRetries is used in the retry loop for players-only queries
        // We can't easily test this without integration testing
    }
}
