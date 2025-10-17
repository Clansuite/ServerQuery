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
 * Test parsing of Etqw fixture captured from live server.
 */
class EtqwFixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/etqw/vv1_5_12663_12663';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_178_162_135_83_27735.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        $serverInfo = $data['server_info'];
        $this->assertEquals(true, $serverInfo['online'], 'Server should be online');
        $this->assertEquals('^7EU Vanilla^8| ^1Makron^9 Horde ', $serverInfo['servertitle'], 'Server title should match');
        $this->assertEquals('maps/slipgate', $serverInfo['mapname'], 'Map should match');
        $this->assertEquals(0, $serverInfo['numplayers'], 'Player count should be 0');
        $this->assertEquals(32, $serverInfo['maxplayers'], 'Max players should be 32');
        $this->assertEquals('sdGameRulesCampaign', $serverInfo['gametype'], 'Game type should match');
    }
}
