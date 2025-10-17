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
 * Test parsing of Just Cause 2 fixture.
 */
class Jc2FixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/jc2/v';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_204_44_116_72_7777.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        $serverInfo = $data['server_info'];
        $this->assertEquals(true, $serverInfo['online'], 'Server should be online');
        $this->assertEquals('[USA/EU] Hasbo\'s |FREEROAM|FREE BUYMENU|FACTIONS|TP|', $serverInfo['servertitle'], 'Server title should match');
        $this->assertEquals('Panau', $serverInfo['mapname'], 'Map should match');
        $this->assertEquals(7, $serverInfo['numplayers'], 'Player count should be 7');
        $this->assertEquals(500, $serverInfo['maxplayers'], 'Max players should be 500');
        $this->assertEquals('Just Cause 2 Multiplayer', $serverInfo['gamename'], 'Game name should match');
        $this->assertEquals('1.0', $serverInfo['gameversion'], 'Game version should match');
        $this->assertEquals('Freeroam', $serverInfo['gametype'], 'Game type should match');

        // Test players
        $this->assertCount(7, $serverInfo['players'], 'Should have 7 players');
        $this->assertEquals('That1EdgyJohn', $serverInfo['players'][0]['name'], 'First player name should match');
        $this->assertEquals('STEAM_0:1:80615257', $serverInfo['players'][0]['steamid'], 'First player SteamID should match');
        $this->assertEquals(69, $serverInfo['players'][0]['ping'], 'First player ping should match');

        // Test rules
        $this->assertArrayHasKey('rules', $serverInfo, 'Should have rules');
        $this->assertEquals('true', $serverInfo['rules']['dedicated'], 'Dedicated should be true');
        $this->assertEquals('False', $serverInfo['rules']['password'], 'Password should be False');
    }
}
