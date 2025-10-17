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

final class RustFixtureTest extends TestCase
{
    public function testFixtureExistsAndHasMetadata(): void
    {
        $dir = __DIR__ . '/../../../tests/fixtures/rust/v2597';

        $json = $dir . '/capture_216_39_240_160_28015.json';

        $this->assertFileExists($json, 'JSON metadata file should exist');

        $data = \json_decode(\file_get_contents($json), true);
        $this->assertIsArray($data, 'JSON metadata should decode to an array');

        $this->assertArrayHasKey('server_info', $data, 'Metadata must contain server_info');
        $this->assertArrayHasKey('captures', $data, 'Metadata must contain captures array');

        $serverInfo = $data['server_info'];

        $this->assertArrayHasKey('gameversion', $serverInfo);
        $this->assertEquals('2597', (string) $serverInfo['gameversion'], 'Expected gameversion to match captured value');

        $this->assertArrayHasKey('rules', $serverInfo);
        $this->assertEquals('Rust', $data['protocol'] ?? '', 'Metadata protocol should be Rust');
        $this->assertEquals('v2597', $data['normalized_version'] ?? '', 'Normalized version should be v2597');
    }
}
