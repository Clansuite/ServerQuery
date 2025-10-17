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
use Clansuite\ServerQuery\ServerProtocols\Arma;
use PHPUnit\Framework\TestCase;

final class ArmaTest extends TestCase
{
    public function testMetadataProperties(): void
    {
        $arma = new Arma;

        $this->assertSame('Arma', $arma->name);
        $this->assertContains('ArmA Armed Assault', $arma->supportedGames);
        $this->assertSame('arma', $arma->protocol);
        $this->assertContains('ArmA', $arma->game_series_list);
    }

    public function testGetProtocolNameReturnsProtocolIdentifier(): void
    {
        $arma = new Arma;

        $this->assertSame('arma', $arma->getProtocolName());
    }

    public function testGetVersionReturnsGameVersionOrUnknown(): void
    {
        $infoWithVersion = new ServerInfo(
            address: '1.2.3.4',
            queryport: 2302,
            online: true,
            gamename: 'Arma',
            gameversion: '1.2.3',
            servertitle: '',
            mapname: '',
            gametype: '',
            numplayers: 0,
            maxplayers: 0,
            rules: [],
            players: [],
            errstr: null,
        );

        $arma = new Arma;

        $this->assertSame('1.2.3', $arma->getVersion($infoWithVersion));

        $infoWithoutVersion = new ServerInfo(
            address: '1.2.3.4',
            queryport: 2302,
            online: true,
            gamename: 'Arma',
            gameversion: null,
            servertitle: '',
            mapname: '',
            gametype: '',
            numplayers: 0,
            maxplayers: 0,
            rules: [],
            players: [],
            errstr: null,
        );

        $this->assertSame('unknown', $arma->getVersion($infoWithoutVersion));
    }

    public function testQueryUsesParentAndReturnsServerInfoWithoutNetworkCalls(): void
    {
        // Create a tiny subclass to bypass network calls inside query_server
        $testArma = new class extends Arma
        {
            public function query_server(bool $getPlayers = true, bool $getRules = true): bool
            {
                // Simulate a successful query and populate properties the parent query() expects
                $this->address     = '127.0.0.1';
                $this->queryport   = 2302;
                $this->online      = true;
                $this->gamename    = 'ArmA';
                $this->gameversion = '0.9-test';
                $this->servertitle = 'Test Server';
                $this->mapname     = 'testmap';
                $this->gametype    = 'DM';
                $this->numplayers  = 1;
                $this->maxplayers  = 16;
                $this->rules       = ['friendlyfire' => '0'];
                $this->players     = [['name' => 'player1', 'score' => '10']];

                return true;
            }
        };

        $addr = new ServerAddress('127.0.0.1', 2302);

        $info = $testArma->query($addr);

        $this->assertInstanceOf(ServerInfo::class, $info);
        $this->assertSame('127.0.0.1', $info->address);
        $this->assertSame(2302, $info->queryport);
        $this->assertTrue($info->online);
        $this->assertSame('ArmA', $info->gamename);
        $this->assertSame('0.9-test', $info->gameversion);
        $this->assertSame('Test Server', $info->servertitle);
        $this->assertSame('testmap', $info->mapname);
        $this->assertSame('DM', $info->gametype);
        $this->assertSame(1, $info->numplayers);
        $this->assertSame(16, $info->maxplayers);
        $this->assertSame(['friendlyfire' => '0'], $info->rules);
        $this->assertSame([['name' => 'player1', 'score' => '10']], $info->players);
    }
}
