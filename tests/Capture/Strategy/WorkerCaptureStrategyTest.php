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
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\Capture\Strategy\CaptureStrategyInterface;
use Clansuite\Capture\Strategy\WorkerCaptureStrategy;
use PHPUnit\Framework\TestCase;

final class WorkerCaptureStrategyTest extends TestCase
{
    public function testConstructorWithDefaultTimeout(): void
    {
        $strategy = new WorkerCaptureStrategy;

        $this->assertInstanceOf(WorkerCaptureStrategy::class, $strategy);
    }

    public function testConstructorWithCustomTimeout(): void
    {
        $strategy = new WorkerCaptureStrategy(15);

        $this->assertInstanceOf(WorkerCaptureStrategy::class, $strategy);
    }

    public function testImplementsCaptureStrategyInterface(): void
    {
        $strategy = new WorkerCaptureStrategy;

        $this->assertInstanceOf(CaptureStrategyInterface::class, $strategy);
    }

    public function testCaptureReturnsCaptureResult(): void
    {
        $strategy = new WorkerCaptureStrategy(1); // Use short timeout to fail quickly

        $protocol = $this->createMock(ProtocolInterface::class);
        $protocol->expects($this->once())
            ->method('getProtocolName')
            ->willReturn('source');

        $addr    = new ServerAddress('127.0.0.1', 27015);
        $options = ['protocol_name' => 'source'];

        // Note: This test will actually perform a real network query
        // In a real test environment, you might want to mock the network calls
        // For now, we'll test that the method returns the expected type
        $result = $strategy->capture($protocol, $addr, $options);

        $this->assertInstanceOf(CaptureResult::class, $result);
        $this->assertIsArray($result->rawPackets);
        $this->assertInstanceOf(ServerInfo::class, $result->serverInfo);
        $this->assertIsArray($result->metadata);
        $this->assertArrayHasKey('worker_used', $result->metadata);
        $this->assertTrue($result->metadata['worker_used']);
    }

    public function testCaptureWithDefaultProtocolName(): void
    {
        $strategy = new WorkerCaptureStrategy(1); // Use short timeout to fail quickly

        $protocol = $this->createMock(ProtocolInterface::class);
        $protocol->expects($this->once())
            ->method('getProtocolName')
            ->willReturn('custom');

        $addr    = new ServerAddress('127.0.0.1', 27015);
        $options = []; // No protocol_name specified, should default to 'source'

        $result = $strategy->capture($protocol, $addr, $options);

        $this->assertInstanceOf(CaptureResult::class, $result);
        $this->assertEquals('custom', $result->metadata['protocol']); // Should use protocol name from interface
    }

    public function testCaptureMetadataStructure(): void
    {
        $strategy = new WorkerCaptureStrategy(1); // Use short timeout to fail quickly instead of making real network calls

        $protocol = $this->createMock(ProtocolInterface::class);
        $protocol->expects($this->once())
            ->method('getProtocolName')
            ->willReturn('quake3');

        $addr    = new ServerAddress('192.168.1.1', 7777);
        $options = ['protocol_name' => 'quake3'];

        $result = $strategy->capture($protocol, $addr, $options);

        $this->assertInstanceOf(CaptureResult::class, $result);
        $this->assertEquals([
            'ip'          => '192.168.1.1',
            'port'        => 7777,
            'protocol'    => 'quake3',
            'timestamp'   => $result->metadata['timestamp'],
            'worker_used' => true,
        ], $result->metadata);
    }
}
