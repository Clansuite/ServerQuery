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
 * Test parsing of Ffow fixture.
 */
class FfowFixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/ffow/v';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_216_245_177_45_5476.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        $serverInfo = $data['server_info'];
        $this->assertEquals(true, $serverInfo['online'], 'Server should be online');
        $this->assertEquals('Frontlines Fuel of War Server', $serverInfo['servertitle'], 'Server title should match');
        $this->assertEquals('FFOW-Map01', $serverInfo['mapname'], 'Map should match');
        $this->assertEquals(2, $serverInfo['numplayers'], 'Player count should be 2');
        $this->assertEquals(16, $serverInfo['maxplayers'], 'Max players should be 16');
        $this->assertEquals('TeamDeathMatch', $serverInfo['gametype'], 'Game type should match');
        $this->assertCount(2, $serverInfo['players'], 'Should have 2 players');
        $this->assertEquals('Player1', $serverInfo['players'][0]['name'], 'First player name should match');
        $this->assertEquals(25, $serverInfo['players'][0]['score'], 'First player score should match');
    }
}
