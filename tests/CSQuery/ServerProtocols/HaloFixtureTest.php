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
 * Test parsing of Halo fixture.
 */
class HaloFixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/halo/v';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_64_74_97_107_2302.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        $serverInfo = $data['server_info'];
        $this->assertEquals(true, $serverInfo['online'], 'Server should be online');
        $this->assertEquals('POQclan.com PC16', $serverInfo['servertitle'], 'Server title should match');
        $this->assertEquals('bloodgulch', $serverInfo['mapname'], 'Map should match');
        $this->assertEquals(13, $serverInfo['numplayers'], 'Player count should be 13');
        $this->assertEquals(16, $serverInfo['maxplayers'], 'Max players should be 16');
        $this->assertEquals('CTF', $serverInfo['gametype'], 'Game type should match');
        $this->assertCount(13, $serverInfo['players'], 'Should have 13 players');
        $this->assertEquals('Hooded_Claw', $serverInfo['players'][0]['player'], 'First player name should match');
        $this->assertEquals('1', $serverInfo['players'][0]['score'], 'First player score should match');
    }
}
