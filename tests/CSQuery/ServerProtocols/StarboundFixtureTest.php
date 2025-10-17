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

final class StarboundFixtureTest extends TestCase
{
    public function testFixtureExistsAndHasMetadata(): void
    {
        $dir = __DIR__ . '/../../../tests/fixtures/starbound/v';

        $json = $dir . '/capture_144_217_255_176_21025.json';

        // Note: Fixture may not exist if capture failed due to offline servers
        if (!\file_exists($json)) {
            $this->markTestSkipped('Fixture file does not exist - servers may be offline');
        }

        $this->assertFileExists($json, 'JSON metadata file should exist');

        $data = \json_decode(\file_get_contents($json), true);
        $this->assertIsArray($data, 'JSON metadata should decode to an array');

        $this->assertArrayHasKey('server_info', $data, 'Metadata must contain server_info');
    }
}
