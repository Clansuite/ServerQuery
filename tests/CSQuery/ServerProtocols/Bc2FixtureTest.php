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
 * Test parsing of Battlefield Bad Company 2 fixture captured from live server.
 */
class Bc2FixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/bc2/v602833';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_5_149_214_28_19567.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        // Check required data
        $serverInfo = $data['server_info'];
        $this->assertEquals('5.149.214.28', $serverInfo['address']);
        $this->assertEquals(19567, $serverInfo['queryport']);
        $this->assertTrue($serverInfo['online']);
        $this->assertEquals('#1 VANILLA ONSLAUGHT 24/7 + BOTS', $serverInfo['servertitle']);
        $this->assertEquals('Levels/BC1_Oasis_CQ', $serverInfo['mapname']);
        $this->assertEquals(25, $serverInfo['numplayers']);
        $this->assertEquals(32, $serverInfo['maxplayers']);
        $this->assertNotEmpty($serverInfo['players']);
        $this->assertNotEmpty($serverInfo['rules']);
    }
}
