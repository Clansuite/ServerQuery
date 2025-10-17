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

final class ArmaReforgerFixtureTest extends TestCase
{
    public function testFixtureExistsAndHasMetadata(): void
    {
        $dir = __DIR__ . '/../../../tests/fixtures/armareforger/v1_0_0';

        $json = $dir . '/capture_185_249_196_10_17777.json';

        $this->assertFileExists($json, 'JSON metadata file should exist');

        $data = \json_decode(\file_get_contents($json), true);
        $this->assertIsArray($data, 'JSON metadata should decode to an array');

        $this->assertArrayHasKey('server_info', $data, 'Metadata must contain server_info');
    }
}
