<?php

declare(strict_types=1);

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
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Clansuite\ServerQuery\ServerProtocols\Bf1942;
use PHPUnit\Framework\TestCase;

final class Bf1942Test extends TestCase
{
    public function testConstructorWithDefaultValues(): void
    {
        $protocol = new Bf1942;

        $this->assertInstanceOf(Bf1942::class, $protocol);
        $this->assertEquals('Battlefield 1942', $protocol->name);
        $this->assertEquals(['Battlefield 1942'], $protocol->supportedGames);
        $this->assertEquals('bf1942', $protocol->protocol);
    }

    public function testConstructorWithAddressAndPort(): void
    {
        $protocol = new Bf1942('127.0.0.1', 14567);

        $this->assertEquals('127.0.0.1', $protocol->address);
        $this->assertEquals(14567, $protocol->queryport);
    }

    public function testGetProtocolName(): void
    {
        $protocol = new Bf1942;

        $this->assertEquals('bf1942', $protocol->getProtocolName());
    }

    public function testGetVersionWithValidServerInfo(): void
    {
        $protocol   = new Bf1942;
        $serverInfo = new ServerInfo(
            address: '127.0.0.1',
            queryport: 14567,
            online: true,
            gamename: 'Battlefield 1942',
            gameversion: '1.6.19',
            servertitle: 'Test BF1942 Server',
            mapname: 'wake',
            gametype: 'Conquest',
            numplayers: 12,
            maxplayers: 32,
            rules: [],
            players: [],
            errstr: '',
        );

        $this->assertEquals('1.6.19', $protocol->getVersion($serverInfo));
    }

    public function testGetVersionWithMissingVersion(): void
    {
        $protocol   = new Bf1942;
        $serverInfo = new ServerInfo(
            address: '127.0.0.1',
            queryport: 14567,
            online: false,
            gamename: 'Battlefield 1942',
            gameversion: null,
            servertitle: 'Test Server',
            mapname: 'wake',
            gametype: 'Conquest',
            numplayers: 0,
            maxplayers: 32,
            rules: [],
            players: [],
            errstr: 'Server offline',
        );

        $this->assertEquals('unknown', $protocol->getVersion($serverInfo));
    }

    public function testParseResponseWithBasicServerInfo(): void
    {
        $protocol = new Bf1942;

        $response = '\\hostname\\BF1942 Test Server\\mapname\\wake\\gametype\\Conquest\\maxplayers\\32\\numplayers\\12\\password\\0\\';

        $reflection  = new ReflectionClass($protocol);
        $parseMethod = $reflection->getMethod('parseResponse');
        $parseMethod->setAccessible(true);

        $parseMethod->invoke($protocol, $response);

        $this->assertEquals('BF1942 Test Server', $protocol->servertitle);
        $this->assertEquals('wake', $protocol->mapname);
        $this->assertEquals('Conquest', $protocol->gametype);
        $this->assertEquals(32, $protocol->maxplayers);
        $this->assertEquals(12, $protocol->numplayers);
        $this->assertEquals(0, $protocol->password);
    }

    public function testParseResponseWithPlayers(): void
    {
        $protocol = new Bf1942;

        $response = '\\hostname\\Player Test Server\\numplayers\\2\\maxplayers\\16\\playername\\Player1\\score\\1500\\ping\\50\\playername\\Player2\\score\\1200\\ping\\75\\';

        $reflection  = new ReflectionClass($protocol);
        $parseMethod = $reflection->getMethod('parseResponse');
        $parseMethod->setAccessible(true);

        $parseMethod->invoke($protocol, $response);

        $this->assertEquals('Player Test Server', $protocol->servertitle);
        $this->assertEquals(2, $protocol->numplayers);
        $this->assertEquals(16, $protocol->maxplayers);
        $this->assertCount(2, $protocol->players);

        $this->assertEquals('Player1', $protocol->players[0]['name']);
        $this->assertEquals('1500', $protocol->players[0]['score']);
        $this->assertEquals('50', $protocol->players[0]['ping']);

        $this->assertEquals('Player2', $protocol->players[1]['name']);
        $this->assertEquals('1200', $protocol->players[1]['score']);
        $this->assertEquals('75', $protocol->players[1]['ping']);
    }

    public function testParseResponseWithRules(): void
    {
        $protocol = new Bf1942;

        $response = '\\hostname\\Rules Test Server\\maxplayers\\24\\numplayers\\8\\timelimit\\30\\fraglimit\\50\\customrule\\customvalue\\';

        $reflection  = new ReflectionClass($protocol);
        $parseMethod = $reflection->getMethod('parseResponse');
        $parseMethod->setAccessible(true);

        $parseMethod->invoke($protocol, $response);

        $this->assertEquals('Rules Test Server', $protocol->servertitle);
        $this->assertEquals(24, $protocol->maxplayers);
        $this->assertEquals(8, $protocol->numplayers);
        $this->assertEquals('30', $protocol->rules['timelimit']);
        $this->assertEquals('50', $protocol->rules['fraglimit']);
        $this->assertEquals('customvalue', $protocol->rules['customrule']);
    }

    public function testParseResponseWithLeadingTrailingBackslashes(): void
    {
        $protocol = new Bf1942;

        $response = '\\\\hostname\\Server With Backslashes\\mapname\\testmap\\\\';

        $reflection  = new ReflectionClass($protocol);
        $parseMethod = $reflection->getMethod('parseResponse');
        $parseMethod->setAccessible(true);

        $parseMethod->invoke($protocol, $response);

        $this->assertEquals('Server With Backslashes', $protocol->servertitle);
        $this->assertEquals('testmap', $protocol->mapname);
    }

    public function testParseResponseWithEmptyFirstElement(): void
    {
        $protocol = new Bf1942;

        // Create a response that results in an empty first element after processing
        // This simulates malformed or edge case responses
        $response = '\\';  // Just a single backslash

        $reflection  = new ReflectionClass($protocol);
        $parseMethod = $reflection->getMethod('parseResponse');
        $parseMethod->setAccessible(true);

        $parseMethod->invoke($protocol, $response);

        // With just a backslash, after trim it becomes empty string
        // explode on empty string gives [''] so array_shift should be called
        // But no actual key-value pairs to parse
        $this->assertEquals('', $protocol->servertitle);
    }

    public function testParseResponseWithPasswordEnabled(): void
    {
        $protocol = new Bf1942;

        $response = '\\hostname\\Password Protected Server\\password\\1\\maxplayers\\16\\';

        $reflection  = new ReflectionClass($protocol);
        $parseMethod = $reflection->getMethod('parseResponse');
        $parseMethod->setAccessible(true);

        $parseMethod->invoke($protocol, $response);

        $this->assertEquals('Password Protected Server', $protocol->servertitle);
        $this->assertEquals(1, $protocol->password);
        $this->assertEquals(16, $protocol->maxplayers);
    }

    public function testQueryMethod(): void
    {
        $protocol = new Bf1942;

        $addr = new ServerAddress('127.0.0.1', 14567);

        // Test that query method returns ServerInfo
        // Note: This will make a real UDP query in test environment
        // In production, this should be mocked

        $result = $protocol->query($addr);

        $this->assertInstanceOf(ServerInfo::class, $result);
        $this->assertEquals('127.0.0.1', $result->address);
        $this->assertEquals(14567, $result->queryport);
        // Other assertions depend on actual server response
    }

    public function testBf1942ClassExists(): void
    {
        $this->assertTrue(\class_exists(Bf1942::class), 'Bf1942 class should exist');
    }

    public function testBf1942ExtendsCSQuery(): void
    {
        $reflection = new ReflectionClass(Bf1942::class);
        $this->assertTrue($reflection->isSubclassOf(CSQuery::class), 'Bf1942 should extend CSQuery');
    }
}
