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
 * Test parsing of DDnet fixture captured from live server.
 */
class DdnetFixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/ddnet/v';

    public function testFixtureParsing(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_ger10_ddnet_org_8300.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');

        $serverInfo = $data['server_info'];

        // The fixture should show online status as true after our fix
        $this->assertEquals(true, $serverInfo['online'], 'Server should be online');

        // Check that we have server details
        $this->assertArrayHasKey('rules', $serverInfo, 'Server info should contain rules');
        $this->assertEquals('0', $serverInfo['rules']['flags'], 'Server should be DDraceNetwork');

        // Check players
        $this->assertArrayHasKey('players', $serverInfo, 'Server info should contain players');
        $this->assertGreaterThan(0, \count($serverInfo['players']), 'Should have players');
    }
}
