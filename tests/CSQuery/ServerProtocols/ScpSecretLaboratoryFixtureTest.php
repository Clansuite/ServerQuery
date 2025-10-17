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
 * Test parsing of SCP: Secret Laboratory fixture captured from live server.
 */
class ScpSecretLaboratoryFixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/scpsecretlaboratory/v';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_127_0_0_1_27015.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        // Check required data
        $serverInfo = $data['server_info'];
        $this->assertEquals('127.0.0.1', $serverInfo['address']);
        $this->assertEquals(27015, $serverInfo['queryport']);
        $this->assertFalse($serverInfo['online']);
        $this->assertEquals('', $serverInfo['servertitle']);
        $this->assertEquals(0, $serverInfo['numplayers']);
        $this->assertEquals(0, $serverInfo['maxplayers']);
    }
}
