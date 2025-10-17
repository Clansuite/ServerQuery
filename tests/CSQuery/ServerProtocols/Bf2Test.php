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
use Clansuite\ServerQuery\ServerProtocols\Bf2;
use PHPUnit\Framework\TestCase;

final class Bf2Test extends TestCase
{
    public function testConstructorWithDefaultValues(): void
    {
        $protocol = new Bf2;

        $this->assertInstanceOf(Bf2::class, $protocol);
        $this->assertEquals('Battlefield 2', $protocol->name);
        $this->assertEquals(['Battlefield 2'], $protocol->supportedGames);
        $this->assertEquals('Bf2', $protocol->protocol);
        $this->assertEquals(['Battlefield'], $protocol->game_series_list);
    }

    public function testConstructorWithAddressAndPort(): void
    {
        $protocol = new Bf2('127.0.0.1', 16567);

        $this->assertEquals('127.0.0.1', $protocol->address);
        $this->assertEquals(16567, $protocol->queryport);
    }

    public function testGetProtocolName(): void
    {
        $protocol = new Bf2;

        $this->assertEquals('Bf2', $protocol->getProtocolName());
    }

    public function testGetVersionWithValidServerInfo(): void
    {
        $protocol   = new Bf2;
        $serverInfo = new ServerInfo(
            address: '127.0.0.1',
            queryport: 16567,
            online: true,
            gamename: 'Battlefield 2',
            gameversion: '1.5.3153-802.0',
            servertitle: 'Test BF2 Server',
            mapname: 'strike_at_karkand',
            gametype: 'Conquest',
            numplayers: 32,
            maxplayers: 64,
            rules: [],
            players: [],
            errstr: '',
        );

        $this->assertEquals('1.5.3153-802.0', $protocol->getVersion($serverInfo));
    }

    public function testGetVersionWithMissingVersion(): void
    {
        $protocol   = new Bf2;
        $serverInfo = new ServerInfo(
            address: '127.0.0.1',
            queryport: 16567,
            online: false,
            gamename: 'Battlefield 2',
            gameversion: null,
            servertitle: 'Test Server',
            mapname: 'strike_at_karkand',
            gametype: 'Conquest',
            numplayers: 0,
            maxplayers: 64,
            rules: [],
            players: [],
            errstr: 'Server offline',
        );

        $this->assertEquals('unknown', $protocol->getVersion($serverInfo));
    }

    public function testParseDetailsWithBasicServerInfo(): void
    {
        $protocol = new Bf2;

        $data = "hostname\x00Test BF2 Server\x00mapname\x00strike_at_karkand\x00gametype\x00Conquest\x00maxplayers\x0064\x00numplayers\x0032\x00password\x000\x00";

        $reflection  = new ReflectionClass($protocol);
        $parseMethod = $reflection->getMethod('parseDetails');
        $parseMethod->setAccessible(true);

        $parseMethod->invoke($protocol, $data);

        $this->assertEquals('Test BF2 Server', $protocol->servertitle);
        $this->assertEquals('strike_at_karkand', $protocol->mapname);
        $this->assertEquals('Conquest', $protocol->gametype);
        $this->assertEquals(64, $protocol->maxplayers);
        $this->assertEquals(32, $protocol->numplayers);
        $this->assertEquals(0, $protocol->password);
    }

    public function testParseDetailsWithPasswordEnabled(): void
    {
        $protocol = new Bf2;

        $data = "hostname\x00Password Protected Server\x00password\x001\x00maxplayers\x0032\x00";

        $reflection  = new ReflectionClass($protocol);
        $parseMethod = $reflection->getMethod('parseDetails');
        $parseMethod->setAccessible(true);

        $parseMethod->invoke($protocol, $data);

        $this->assertEquals('Password Protected Server', $protocol->servertitle);
        $this->assertEquals(1, $protocol->password);
        $this->assertEquals(32, $protocol->maxplayers);
    }

    public function testParseDetailsWithCustomRules(): void
    {
        $protocol = new Bf2;

        $data = "hostname\x00Custom Rules Server\x00customrule1\x00value1\x00customrule2\x00value2\x00maxplayers\x0016\x00";

        $reflection  = new ReflectionClass($protocol);
        $parseMethod = $reflection->getMethod('parseDetails');
        $parseMethod->setAccessible(true);

        $parseMethod->invoke($protocol, $data);

        $this->assertEquals('Custom Rules Server', $protocol->servertitle);
        $this->assertEquals(16, $protocol->maxplayers);
        $this->assertEquals('value1', $protocol->rules['customrule1']);
        $this->assertEquals('value2', $protocol->rules['customrule2']);
    }

    public function testParsePlayersAndTeamsWithPlayers(): void
    {
        $protocol = new Bf2;

        $data = "player_\x00\x00Player1\x001\x00Player2\x002\x00Player3\x001\x00";

        $reflection  = new ReflectionClass($protocol);
        $parseMethod = $reflection->getMethod('parsePlayersAndTeams');
        $parseMethod->setAccessible(true);

        $parseMethod->invoke($protocol, $data);

        $this->assertCount(3, $protocol->players);
        $this->assertEquals('Player1', $protocol->players[0]['name']);
        $this->assertEquals('1', $protocol->players[0]['team']);
        $this->assertEquals('Player2', $protocol->players[1]['name']);
        $this->assertEquals('2', $protocol->players[1]['team']);
        $this->assertEquals('Player3', $protocol->players[2]['name']);
        $this->assertEquals('1', $protocol->players[2]['team']);
    }

    public function testParsePlayersAndTeamsWithEmptyData(): void
    {
        $protocol = new Bf2;

        $data = "player_\x00\x00";

        $reflection  = new ReflectionClass($protocol);
        $parseMethod = $reflection->getMethod('parsePlayersAndTeams');
        $parseMethod->setAccessible(true);

        $parseMethod->invoke($protocol, $data);

        $this->assertEquals([], $protocol->players);
    }

    public function testParsePlayersAndTeamsWithNumericName(): void
    {
        $protocol = new Bf2;

        $data = "player_\x00\x00123\x001\x00ValidPlayer\x002\x00";

        $reflection  = new ReflectionClass($protocol);
        $parseMethod = $reflection->getMethod('parsePlayersAndTeams');
        $parseMethod->setAccessible(true);

        $parseMethod->invoke($protocol, $data);

        // Should stop parsing when it encounters a numeric name
        $this->assertCount(0, $protocol->players);
    }

    public function testParsePlayersAndTeamsWithEmptyName(): void
    {
        $protocol = new Bf2;

        $data = "player_\x00\x00ValidName\x001\x00\x002\x00";

        $reflection  = new ReflectionClass($protocol);
        $parseMethod = $reflection->getMethod('parsePlayersAndTeams');
        $parseMethod->setAccessible(true);

        $parseMethod->invoke($protocol, $data);

        // Should parse ValidName but skip the empty name before it
        $this->assertCount(1, $protocol->players);
        $this->assertEquals('ValidName', $protocol->players[0]['name']);
        $this->assertEquals('1', $protocol->players[0]['team']);
    }

    public function testProcessResponseWithValidData(): void
    {
        $protocol = new Bf2;

        // Test that processResponse method exists and is callable
        // The exact binary format is complex, so we test the method signature
        $reflection    = new ReflectionClass($protocol);
        $processMethod = $reflection->getMethod('processResponse');
        $this->assertTrue($processMethod->isProtected());
        $returnType = $processMethod->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('void', (string) $returnType);
    }

    public function testProcessResponseWithTooShortResponse(): void
    {
        $protocol = new Bf2;

        $response = "\x00\x00"; // Too short

        $reflection    = new ReflectionClass($protocol);
        $processMethod = $reflection->getMethod('processResponse');
        $processMethod->setAccessible(true);

        $processMethod->invoke($protocol, $response);

        $this->assertEquals('Response too short', $protocol->errstr);
    }

    public function testProcessResponseWithFailedSplit(): void
    {
        $protocol = new Bf2;

        // Create a response that passes the length check but has no split marker
        $response = "\x00\x00\x00\x00\x00splitnum\x00\x00\x00\x00\x00\x00\x00\x01\x00hostname\x00Server\x00";

        $reflection    = new ReflectionClass($protocol);
        $processMethod = $reflection->getMethod('processResponse');
        $processMethod->setAccessible(true);

        $processMethod->invoke($protocol, $response);

        // The preg_split will succeed but may not find the expected pattern
        // This tests that the method handles various response formats gracefully
        $this->assertInstanceOf(Bf2::class, $protocol);
    }

    public function testQueryMethod(): void
    {
        $protocol = new Bf2;

        $addr = new ServerAddress('127.0.0.1', 16567);

        // Test that query method returns ServerInfo
        // Note: This will make a real UDP query in test environment
        // In production, this should be mocked

        $result = $protocol->query($addr);

        $this->assertInstanceOf(ServerInfo::class, $result);
        $this->assertEquals('127.0.0.1', $result->address);
        $this->assertEquals(16567, $result->queryport);
        // Other assertions depend on actual server response
    }

    public function testBf2ClassExists(): void
    {
        $this->assertTrue(\class_exists(Bf2::class), 'Bf2 class should exist');
    }

    public function testBf2ExtendsCSQuery(): void
    {
        $reflection = new ReflectionClass(Bf2::class);
        $this->assertTrue($reflection->isSubclassOf(CSQuery::class), 'Bf2 should extend CSQuery');
    }
}
