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
 * Test parsing of MW3 fixture captured from live server.
 */
class Mw3FixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/source/v';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_74_91_113_218_27015.json';

        if (!\file_exists($jsonFile)) {
            $this->markTestSkipped('JSON fixture missing');
        }

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        $serverInfo = $data['server_info'];
        // Since the server was offline during capture, expect offline
        $this->assertEquals(false, $serverInfo['online'], 'Server should be offline');
        $this->assertEquals('', $serverInfo['servertitle'], 'Server title should be empty');
        $this->assertEquals('', $serverInfo['mapname'], 'Map should be empty');
        $this->assertEquals(0, $serverInfo['numplayers'], 'Player count should be 0');
        $this->assertEquals(0, $serverInfo['maxplayers'], 'Max players should be 0');
    }
}
