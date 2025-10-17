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
use function base64_encode;
use function serialize;
use Clansuite\ServerQuery\CSQuery;
use Clansuite\ServerQuery\ServerProtocols\ArkSurvivalEvolved;
use Clansuite\ServerQuery\ServerProtocols\Battlefield4;
use Clansuite\ServerQuery\ServerProtocols\CounterStrike16;
use Clansuite\ServerQuery\ServerProtocols\Csgo;
use Clansuite\ServerQuery\ServerProtocols\Quake3Arena;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CSQueryTest extends TestCase
{
    private CSQuery $factory;

    protected function setUp(): void
    {
        $this->factory = new CSQuery;
    }

    public function testConstructorSetsAddressAndPort(): void
    {
        $instance = new CSQuery;

        $this->assertEquals('', $instance->address);
        $this->assertEquals(0, $instance->queryport);
    }

    public function testCreateInstanceReturnsCorrectProtocolInstances(): void
    {
        // Test various protocol mappings
        $protocols = [
            'Quake3a'         => Quake3Arena::class,
            'Steam'           => Steam::class,
            'Csgo'            => Csgo::class,
            'Bf4'             => Battlefield4::class,
            'Arkse'           => ArkSurvivalEvolved::class,
            'Cs16'            => CounterStrike16::class,
            'CounterStrike16' => CounterStrike16::class,
        ];

        foreach ($protocols as $protocolName => $expectedClass) {
            $instance = $this->factory->createInstance($protocolName, '127.0.0.1', 27015);
            $this->assertInstanceOf($expectedClass, $instance, "Protocol '{$protocolName}' should create instance of {$expectedClass}");
            $this->assertEquals('127.0.0.1', $instance->address);
            $this->assertEquals(27015, $instance->queryport);
        }
    }

    public function testCreateInstanceWithUnknownProtocolReturnsProtocolName(): void
    {
        // For unknown protocols, createInstance should throw an exception
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Protocol 'UnknownProtocol' is not supported.");
        $this->factory->createInstance('UnknownProtocol', '127.0.0.1', 27015);
    }

    public function testGetSupportedProtocolsReturnsArray(): void
    {
        $protocols = $this->factory->getSupportedProtocols();

        $this->assertIsArray($protocols);
        $this->assertNotEmpty($protocols);
        $this->assertContains('Csgo', $protocols);
        $this->assertContains('Steam', $protocols);
        $this->assertContains('Quake3a', $protocols);
        $this->assertContains('Citadel', $protocols);
        $this->assertContains('Pixark', $protocols);
        $this->assertContains('conanexiles', $protocols);
        $this->assertContains('miscreated', $protocols);
        $this->assertContains('zomboid', $protocols);
        $this->assertContains('wurm', $protocols);
        $this->assertContains('tibia', $protocols);
    }

    public function testUnserializeWithValidData(): void
    {
        // Create a mock serialized string
        $testData = [
            'address'     => '127.0.0.1',
            'queryport'   => 27015,
            'servertitle' => 'Test Server',
            'mapname'     => 'test_map',
            'numplayers'  => 5,
            'maxplayers'  => 10,
        ];

        $serialized = 'CSQuery:' . base64_encode(serialize($testData));

        $result = $this->factory->unserialize($serialized);

        $this->assertIsArray($result);
        $this->assertEquals('127.0.0.1', $result['address']);
        $this->assertEquals(27015, $result['queryport']);
        $this->assertEquals('Test Server', $result['servertitle']);
    }

    public function testGetNativeJoinURIReturnsFalse(): void
    {
        $instance = new CSQuery;
        $uri      = $instance->getNativeJoinURI();
        $this->assertFalse($uri);
    }

    public function testSortPlayersByName(): void
    {
        $players = [
            ['name' => 'Charlie', 'score' => 10],
            ['name' => 'Alice', 'score' => 5],
            ['name' => 'Bob', 'score' => 15],
        ];

        $sorted = $this->factory->sortPlayers($players, 'name');

        $names = array_column($sorted, 'name');
        $this->assertEquals(['Alice', 'Bob', 'Charlie'], $names);
    }

    public function testSortPlayersByScore(): void
    {
        $players = [
            ['name' => 'Alice', 'score' => 5],
            ['name' => 'Bob', 'score' => 15],
            ['name' => 'Charlie', 'score' => 10],
        ];

        $sorted = $this->factory->sortPlayers($players, 'score');

        $scores = array_column($sorted, 'score');
        // Score sorting is descending (highest first)
        $this->assertEquals([15, 10, 5], $scores);
    }

    public function testSortPlayersWithEmptyArray(): void
    {
        $players = [];
        $sorted  = $this->factory->sortPlayers($players);
        $this->assertEquals([], $sorted);
    }

    public function testQueryServerReturnsFalse(): void
    {
        // Base CSQuery class should return false for query_server
        $result = $this->factory->query_server();
        $this->assertFalse($result);
        $this->assertEquals('This class cannot be used to query a server', $this->factory->errstr);
    }

    public function testSleepReturnsSerializableProperties(): void
    {
        $instance   = new CSQuery;
        $sleepProps = $instance->__sleep();

        $expectedProps = [
            'address',
            'queryport',
            'gamename',
            'hostport',
            'online',
            'gameversion',
            'servertitle',
            'mapname',
            'maptitle',
            'gametype',
            'numplayers',
            'maxplayers',
            'password',
            'nextmap',
            'players',
            'playerkeys',
            'playerteams',
            'maplist',
            'rules',
            'errstr',
        ];

        $this->assertEquals($expectedProps, $sleepProps);
    }

    public function testCsgoProtocolHasCorrectMetadata(): void
    {
        $instance = $this->factory->createInstance('Csgo', '127.0.0.1', 27015);

        $this->assertEquals('Counter-Strike: Global Offensive', $instance->name);
        $this->assertContains('Counter-Strike: Global Offensive', $instance->supportedGames);
    }

    public function testCsgoGetNativeJoinURIReturnsSteamURI(): void
    {
        $instance           = $this->factory->createInstance('Csgo', '192.168.1.100', 27015);
        $instance->hostport = 27015;

        $uri = $instance->getNativeJoinURI();
        $this->assertEquals('steam://connect/192.168.1.100:27015', $uri);
    }
}
