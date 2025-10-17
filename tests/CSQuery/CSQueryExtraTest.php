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

namespace Tests\CSQuery;

use function array_column;
use Clansuite\ServerQuery\CSQuery;
use Clansuite\ServerQuery\Util\UdpClient;
use PHPUnit\Framework\TestCase;

class CSQueryExtraTest extends TestCase
{
    public function testToJsonReturnsJsonRepresentation(): void
    {
        $q              = new CSQuery;
        $q->servertitle = 'JSON Server';
        $q->address     = '1.2.3.4';
        $q->hostport    = 1234;

        $json = $q->toJson();

        $this->assertIsString($json);
        $this->assertStringContainsString('JSON Server', $json);
        $this->assertStringContainsString('1.2.3.4', $json);
    }

    public function testToHtmlIncludesPlayersAndMetadata(): void
    {
        $q              = new CSQuery;
        $q->servertitle = 'HTML Server';
        $q->address     = '8.8.8.8';
        $q->hostport    = 27015;
        $q->gamename    = 'TestGame';
        $q->mapname     = 'testmap';
        $q->numplayers  = 2;
        $q->maxplayers  = 16;
        $q->online      = true;
        $q->players     = [
            ['name' => 'Alice'],
            ['name' => 'Bob'],
        ];

        $html = $q->toHtml();

        $this->assertStringContainsString('<h1>HTML Server</h1>', $html);
        $this->assertStringContainsString('Address: 8.8.8.8:27015', $html);
        $this->assertStringContainsString('Game: TestGame', $html);
        $this->assertStringContainsString('<li>Alice</li>', $html);
        $this->assertStringContainsString('<li>Bob</li>', $html);
    }

    public function testSortPlayersByKillsOrdersDescending(): void
    {
        $players = [
            ['name' => 'P1', 'kills' => 2],
            ['name' => 'P2', 'kills' => 5],
            ['name' => 'P3', 'kills' => 3],
        ];

        $q = new CSQuery;

        $sorted = $q->sortPlayers($players, 'kills');

        $names = array_column($sorted, 'name');
        $this->assertEquals(['P2', 'P3', 'P1'], $names);
    }

    public function testSendCommandHandlesNoResponseAndRecordsDebug(): void
    {
        // Create a test subclass to expose protected sendCommand and debug
        $sub = new class('127.0.0.1', 27015) extends CSQuery
        {
            public function callSendCommand(string $address, int $port, string $command)
            {
                return $this->sendCommand($address, $port, $command);
            }

            public function getDebug(): array
            {
                return $this->debug;
            }
        };

        // UDP client stub that returns null (no response)
        $udp = new class extends UdpClient
        {
            public function query(string $address, int $port, string $packet): ?string
            {
                return null;
            }
        };

        $sub->setUdpClient($udp);

        $res = $sub->callSendCommand('127.0.0.1', 27015, 'CMD');

        $this->assertFalse($res);
        $this->assertContains('-> CMD', $sub->getDebug());
        $this->assertContains('<- (no response)', $sub->getDebug());
    }

    public function testSendCommandReturnsResponseStringAndRecordsDebug(): void
    {
        $sub = new class('127.0.0.1', 27015) extends CSQuery
        {
            public function callSendCommand(string $address, int $port, string $command)
            {
                return $this->sendCommand($address, $port, $command);
            }

            public function getDebug(): array
            {
                return $this->debug;
            }
        };

        $udp = new class extends UdpClient
        {
            public function query(string $address, int $port, string $packet): ?string
            {
                return 'RESPONSE';
            }
        };

        $sub->setUdpClient($udp);

        $res = $sub->callSendCommand('127.0.0.1', 27015, 'HELLO');

        $this->assertSame('RESPONSE', $res);
        $this->assertContains('-> HELLO', $sub->getDebug());
        $this->assertContains('<- RESPONSE', $sub->getDebug());
    }
}
