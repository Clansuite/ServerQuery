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
 * Test parsing of Doom3 fixture captured from live server.
 */
class Doom3FixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/doom3/vv1_3_1_1304';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_66_85_14_240_27666.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        $serverInfo = $data['server_info'];
        $this->assertEquals(true, $serverInfo['online'], 'Server should be online');
        $this->assertEquals('^4T^3h^4e ^3$^4a^3n^4d^3w^4!^3c^4h ^3$^4u^3b^4$^3t^4a^3t^4!^3o^4n', $serverInfo['servertitle'], 'Server title should match');
        $this->assertEquals('swd3dm1', $serverInfo['mapname'], 'Map should match');
        $this->assertEquals(0, $serverInfo['numplayers'], 'Player count should be 0');
        $this->assertEquals(12, $serverInfo['maxplayers'], 'Max players should be 12');
        $this->assertEquals('deathmatch', $serverInfo['gametype'], 'Game type should match');
    }
}
