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
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\ServerProtocols\AssettoCorsa;
use PHPUnit\Framework\TestCase;

final class AssettoCorsaTest extends TestCase
{
    public function testQueryReturnsOfflineWhenNoServerInfo(): void
    {
        $assetto = new class extends AssettoCorsa
        {
            protected function queryEndpoint(ServerAddress $addr, string $path): ?array
            {
                return null;
            }
        };

        $info = $assetto->query(new ServerAddress('127.0.0.1', 9600));

        $this->assertInstanceOf(ServerInfo::class, $info);
        $this->assertFalse($info->online);
    }

    public function testQueryParsesServerAndCarInfo(): void
    {
        $serverInfo = [
            'name'       => 'AC Server',
            'track'      => 'Monza',
            'clients'    => 2,
            'maxclients' => 16,
            'poweredBy'  => 'AC 1.2',
            'pass'       => false,
        ];

        $carInfo = [
            'Cars' => [
                ['IsConnected' => true, 'DriverName' => 'Alice'],
                ['IsConnected' => false, 'DriverName' => 'Bob'],
                ['IsConnected' => true, 'DriverName' => 'Carlos'],
            ],
        ];

        $assetto = new AssettoCorsa;

        // Use reflection to call the private parsers directly
        $info = new ServerInfo;

        $rmServer = new ReflectionMethod(AssettoCorsa::class, 'parseServerInfo');
        $rmServer->setAccessible(true);
        $rmServer->invoke($assetto, $serverInfo, $info);

        $rmCar = new ReflectionMethod(AssettoCorsa::class, 'parseCarInfo');
        $rmCar->setAccessible(true);
        $rmCar->invoke($assetto, $carInfo, $info);

        // After parsing the info should reflect our inputs
        $this->assertSame('AC Server', $info->servertitle);
        $this->assertSame('Monza', $info->mapname);
        $this->assertSame(2, $info->numplayers);
        $this->assertSame(16, $info->maxplayers);
        $this->assertSame('AC 1.2', $info->gameversion);

        $this->assertCount(2, $info->players);
        $this->assertSame('Alice', $info->players[0]['name']);
        $this->assertSame('Carlos', $info->players[1]['name']);
    }

    public function testQueryServerPopulatesClassProperties(): void
    {
        $serverInfo = ['name' => 'AC Server', 'track' => 'Imola', 'clients' => 0, 'maxclients' => 8, 'poweredBy' => 'AC 1.3'];
        $carInfo    = ['Cars' => [['IsConnected' => true, 'DriverName' => 'Solo']]];

        // Create a subclass overriding public query() to avoid private queryEndpoint
        $assetto = new class($serverInfo, $carInfo) extends AssettoCorsa
        {
            private array $server;
            private array $cars;

            public function __construct(array $srv, array $cars)
            {
                parent::__construct('127.0.0.1', 9600);
                $this->server = $srv;
                $this->cars   = $cars;
            }

            public function query(ServerAddress $addr): ServerInfo
            {
                $info = new ServerInfo;

                // Call private parsers via reflection
                $rmServer = new ReflectionMethod(AssettoCorsa::class, 'parseServerInfo');
                $rmServer->setAccessible(true);
                $rmServer->invoke($this, $this->server, $info);

                $rmCar = new ReflectionMethod(AssettoCorsa::class, 'parseCarInfo');
                $rmCar->setAccessible(true);
                $rmCar->invoke($this, $this->cars, $info);

                $info->online = true;

                return $info;
            }
        };

        $this->assertTrue($assetto->query_server());
        $this->assertSame('AC Server', $assetto->servertitle);
        $this->assertSame('Imola', $assetto->mapname);
        $this->assertSame(1, $assetto->numplayers);
        $this->assertSame(8, $assetto->maxplayers);
        $this->assertCount(1, $assetto->players);
    }
}
