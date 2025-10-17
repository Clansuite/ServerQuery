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

use PHPUnit\Framework\TestCase;

/**
 * Test parsing of Killing Floor fixture.
 */
class KillingFloorFixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/killingfloor/v';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_72_5_195_163_7708.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        $serverInfo = $data['server_info'];
        $this->assertEquals(true, $serverInfo['online'], 'Server should be online');
        $this->assertEquals('Ultimate Game Servers.Eu - Hell On Earth | Lvl 40 | Perks | Weapons | Characters | Specimen | Maps | Fast D/L', $serverInfo['servertitle'], 'Server title should match');
        $this->assertEquals('kf-defence-c1', $serverInfo['mapname'], 'Map should match');
        $this->assertEquals(14, $serverInfo['numplayers'], 'Player count should be 14');
        $this->assertEquals(24, $serverInfo['maxplayers'], 'Max players should be 24');
        $this->assertEquals('Killing Floor', $serverInfo['gamename'], 'Game name should match');
        $this->assertEquals('1064', $serverInfo['gameversion'], 'Game version should match');
        $this->assertEquals('KFGameType', $serverInfo['gametype'], 'Game type should match');

        // Test players
        $this->assertCount(14, $serverInfo['players'], 'Should have 14 players');
        $this->assertEquals('Scarz722', $serverInfo['players'][0]['name'], 'First player name should match');
        $this->assertEquals(1615, $serverInfo['players'][0]['score'], 'First player score should match');
        $this->assertEquals(64, $serverInfo['players'][0]['ping'], 'First player ping should match');

        // Test rules
        $this->assertArrayHasKey('rules', $serverInfo, 'Should have rules');
        $this->assertEquals('dedicated', $serverInfo['rules']['servermode'], 'Server mode should be dedicated');
        $this->assertEquals('true', $serverInfo['rules']['isvacsecured'], 'VAC should be secured');
        $this->assertEquals('10', $serverInfo['rules']['finalwave'], 'Final wave should be 10');
    }
}
