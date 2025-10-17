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
use Clansuite\Capture\Strategy\DirectCaptureStrategy;
use PHPUnit\Framework\TestCase;

final class DirectCaptureStrategyTest extends TestCase
{
    public function testCaptureReturnsCaptureResultWithServerInfo(): void
    {
        $strategy = new DirectCaptureStrategy;

        // Mock protocol
        $protocol   = $this->createMock(ProtocolInterface::class);
        $serverInfo = new ServerInfo(
            address: '127.0.0.1',
            queryport: 27015,
            online: true,
            gamename: 'Counter-Strike',
            gameversion: '1.2.3',
            servertitle: 'Test Server',
            mapname: 'de_dust2',
            gametype: 'Classic',
            numplayers: 5,
            maxplayers: 10,
            rules: ['sv_cheats' => '0'],
            players: [['name' => 'Player1']],
            errstr: '',
        );
        $protocol->expects($this->once())
            ->method('query')
            ->willReturn($serverInfo);
        $protocol->expects($this->once())
            ->method('getProtocolName')
            ->willReturn('source');

        $addr    = new ServerAddress('127.0.0.1', 27015);
        $options = [];

        $result = $strategy->capture($protocol, $addr, $options);

        $this->assertInstanceOf(CaptureResult::class, $result);
        $this->assertSame([], $result->rawPackets);
        $this->assertSame($serverInfo, $result->serverInfo);
        $this->assertEquals([
            'ip'        => '127.0.0.1',
            'port'      => 27015,
            'protocol'  => 'source',
            'timestamp' => $result->metadata['timestamp'], // timestamp is dynamic
        ], $result->metadata);
        $this->assertIsInt($result->metadata['timestamp']);
    }

    public function testCaptureWithOptions(): void
    {
        $strategy = new DirectCaptureStrategy;

        $protocol   = $this->createMock(ProtocolInterface::class);
        $serverInfo = new ServerInfo(
            address: '192.168.1.1',
            queryport: 7777,
            online: false,
            errstr: 'Connection failed',
        );
        $protocol->expects($this->once())
            ->method('query')
            ->willReturn($serverInfo);
        $protocol->expects($this->once())
            ->method('getProtocolName')
            ->willReturn('quake3');

        $addr    = new ServerAddress('192.168.1.1', 7777);
        $options = ['some_option' => 'value'];

        $result = $strategy->capture($protocol, $addr, $options);

        $this->assertInstanceOf(CaptureResult::class, $result);
        $this->assertSame([], $result->rawPackets);
        $this->assertSame($serverInfo, $result->serverInfo);
        $this->assertEquals([
            'ip'        => '192.168.1.1',
            'port'      => 7777,
            'protocol'  => 'quake3',
            'timestamp' => $result->metadata['timestamp'],
        ], $result->metadata);
    }
}
