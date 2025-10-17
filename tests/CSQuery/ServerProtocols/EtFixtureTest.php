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

final class EtFixtureTest extends TestCase
{
    public function testFixtureExistsAndHasMetadata(): void
    {
        $dir = __DIR__ . '/../../../tests/fixtures/quake3/vET_Legacy_v2_82_1_linux_i386_Apr_19_2024';

        $json = $dir . '/capture_135_148_137_185_27960.json';

        $this->assertFileExists($json, 'JSON metadata file should exist');

        $data = \json_decode(\file_get_contents($json), true);
        $this->assertIsArray($data, 'JSON metadata should decode to an array');

        $this->assertArrayHasKey('server_info', $data, 'Metadata must contain server_info');
    }
}
