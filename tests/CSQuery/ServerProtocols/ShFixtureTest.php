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
 * Test parsing of SH fixture captured from live server.
 */
class ShFixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/quake/vMedal_of_Honor_Spearhead_2_15_linux_i386_Aug_29_2004';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_68_232_163_58_12203.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        $serverInfo = $data['server_info'];
        $this->assertEquals(true, $serverInfo['online'], 'Server should be online');
        $this->assertEquals('TheWarLegends.com | Rifles Objective Server', $serverInfo['servertitle'], 'Server title should match');
        $this->assertEquals('obj_team3', $serverInfo['mapname'], 'Map should match');
        $this->assertEquals(7, $serverInfo['numplayers'], 'Player count should be 7');
        $this->assertEquals(15, $serverInfo['maxplayers'], 'Max players should be 15');
        $this->assertCount(7, $serverInfo['players'], 'Should have 7 players');
        $this->assertEquals('<TWL>--------Soldier--------', $serverInfo['players'][0]['name'], 'First player name should match');
    }
}
