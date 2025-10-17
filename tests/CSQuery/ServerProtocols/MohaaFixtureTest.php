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
 * Test parsing of MOHAA fixture captured from live server.
 */
class MohaaFixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/quake/vMedal_of_Honor_Allied_Assault_1_11_linux_i386_Jul_22_2004';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_104_53_58_9_12203.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        $serverInfo = $data['server_info'];
        $this->assertEquals(true, $serverInfo['online'], 'Server should be online');
        $this->assertEquals('Public Server : All Welcome', $serverInfo['servertitle'], 'Server title should match');
        $this->assertEquals('mohdm3', $serverInfo['mapname'], 'Map should match');
        $this->assertEquals(3, $serverInfo['numplayers'], 'Player count should be 3');
        $this->assertEquals(31, $serverInfo['maxplayers'], 'Max players should be 31');
        $this->assertCount(3, $serverInfo['players'], 'Should have 3 players');
        $this->assertEquals('Cpl Newkirk', $serverInfo['players'][0]['name'], 'First player name should match');
    }
}
