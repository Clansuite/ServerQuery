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
 * Test parsing of Fear fixture captured from live server.
 */
class FearFixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/fear/v1_08';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_85_215_163_158_27888.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        $serverInfo = $data['server_info'];
        $this->assertEquals(true, $serverInfo['online'], 'Server should be online');
        $this->assertEquals('=FEAR-R-FEAR=', $serverInfo['servertitle'], 'Server title should match');
        $this->assertEquals('[SEC2] - Bypass', $serverInfo['mapname'], 'Map should match');
        $this->assertEquals(0, $serverInfo['numplayers'], 'Player count should be 0');
        $this->assertEquals(20, $serverInfo['maxplayers'], 'Max players should be 20');
        $this->assertEquals('TeamDeathMatch', $serverInfo['gametype'], 'Game type should match');
    }
}
