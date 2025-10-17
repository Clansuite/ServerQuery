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
 * Test parsing of UO fixture captured from live server.
 */
class UoFixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/quake/v1_51';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_74_91_119_166_28960.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        $serverInfo = $data['server_info'];
        $this->assertEquals(true, $serverInfo['online'], 'Server should be online');
        $this->assertEquals('^5[^6VIP^5] New ^2Cod4 Weapons', $serverInfo['servertitle'], 'Server title should match');
        $this->assertEquals('kellys_heroes', $serverInfo['mapname'], 'Map should match');
        $this->assertEquals(1, $serverInfo['numplayers'], 'Player count should be 1');
        $this->assertEquals(64, $serverInfo['maxplayers'], 'Max players should be 64');
        $this->assertCount(1, $serverInfo['players'], 'Should have 1 player');
        $this->assertEquals('{P^1V^7}^5Jewel', $serverInfo['players'][0]['name'], 'Player name should match');
    }
}
