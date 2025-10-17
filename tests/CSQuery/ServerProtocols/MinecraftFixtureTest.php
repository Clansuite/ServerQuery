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

use Clansuite\ServerQuery\ServerProtocols\Minecraft;
use PHPUnit\Framework\TestCase;

/**
 * Test parsing of Minecraft fixture captured from live server.
 */
class MinecraftFixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/minecraft/vRoB_1_7_1_21';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_178_33_34_224_25565.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        // Check required data
        $serverInfo = $data['server_info'];
        $this->assertEquals('178.33.34.224', $serverInfo['address']);
        $this->assertEquals(25565, $serverInfo['queryport']);
        $this->assertTrue($serverInfo['online']);
        $this->assertStringContainsString('Rebirth of Balkan', $serverInfo['servertitle']);
        $this->assertEquals(48, $serverInfo['numplayers']);
        $this->assertEquals(400, $serverInfo['maxplayers']);
        $this->assertEquals('RoB 1.7-1.21', $serverInfo['gameversion']);
    }
}
