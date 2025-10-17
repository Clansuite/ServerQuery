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

final class FactorioFixtureTest extends TestCase
{
    public function testFixtureExistsAndHasMetadata(): void
    {
        $dir = __DIR__ . '/../../../tests/fixtures/factorio/v1_1_110';

        $json = $dir . '/capture_127_0_0_1_34197.json'; // Placeholder

        $this->assertFileExists($json, 'JSON metadata file should exist');

        $data = \json_decode(\file_get_contents($json), true);
        $this->assertIsArray($data, 'JSON metadata should decode to an array');

        $this->assertArrayHasKey('server_info', $data, 'Metadata must contain server_info');
        $this->assertTrue($data['server_info']['online'], 'Server should be online');

        // Test required data elements
        $serverInfo = $data['server_info'];
        $this->assertNotEmpty($serverInfo['servertitle'], 'Server name should not be empty');
        $this->assertGreaterThanOrEqual(0, $serverInfo['numplayers'], 'Player count should be >= 0');
        $this->assertGreaterThan(0, $serverInfo['maxplayers'], 'Max players should be greater than 0');
        $this->assertIsArray($serverInfo['players'], 'Player list should be an array');
        $this->assertIsArray($serverInfo['rules'], 'Server rules should be an array');
    }
}
