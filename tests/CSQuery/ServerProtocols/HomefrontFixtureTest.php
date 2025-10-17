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
 * Test parsing of Homefront fixture.
 */
class HomefrontFixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/homefront/v1_5_500001';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_216_245_177_45_27035.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        $serverInfo = $data['server_info'];
        $this->assertEquals(true, $serverInfo['online'], 'Server should be online');
        $this->assertEquals('Salty Bawls', $serverInfo['servertitle'], 'Server title should match');
        $this->assertEquals('FL-HARBOR', $serverInfo['mapname'], 'Map should match');
        $this->assertEquals(0, $serverInfo['numplayers'], 'Player count should be 0');
        $this->assertEquals(32, $serverInfo['maxplayers'], 'Max players should be 32');
        $this->assertEquals('Homefront', $serverInfo['gamename'], 'Game name should match');
        $this->assertEquals('1.5.500001', $serverInfo['gameversion'], 'Game version should match');
    }
}
