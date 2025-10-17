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

use Clansuite\ServerQuery\ServerProtocols\Samp;
use PHPUnit\Framework\TestCase;

/**
 * Test parsing of SAMP fixture captured from live server.
 */
class SampFixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/samp/v';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_149_202_139_220_7777.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        // Check required data
        $serverInfo = $data['server_info'];
        $this->assertEquals('149.202.139.220', $serverInfo['address']);
        $this->assertEquals(7777, $serverInfo['queryport']);
        $this->assertFalse($serverInfo['online']);
        $this->assertEquals('', $serverInfo['servertitle']);
        $this->assertEquals(0, $serverInfo['numplayers']);
        $this->assertEquals(0, $serverInfo['maxplayers']);
    }
}
