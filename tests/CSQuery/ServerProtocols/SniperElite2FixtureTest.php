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

final class SniperElite2FixtureTest extends TestCase
{
    public function testFixtureExistsAndHasMetadata(): void
    {
        $dir = __DIR__ . '/../../../tests/fixtures/unreal2/v';

        // placeholder - active servers exist but may not respond to queries
        $json = $dir . '/capture_127_0_0_1_7777.json';

        // Note: Sniper Elite V2 is a legacy protocol with servers shut down by publisher around 2024.
        // Servers appearing as "Sniper Elite V2" on GameTracker are actually Sniper Elite 4 using Source protocol.
        if (!\file_exists($json)) {
            $this->markTestSkipped('No active servers exist. Apparent SE2 servers are actually SE4 with Source protocol.');
        }

        $this->assertFileExists($json, 'JSON metadata file should exist');

        $data = \json_decode(\file_get_contents($json), true);
        $this->assertIsArray($data, 'JSON metadata should decode to an array');

        $this->assertArrayHasKey('server_info', $data, 'Metadata must contain server_info');
    }
}
