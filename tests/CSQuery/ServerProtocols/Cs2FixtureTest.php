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

final class Cs2FixtureTest extends TestCase
{
    public function testFixtureMetadataPresence(): void
    {
        $dir = __DIR__ . '/../../../tests/fixtures/cs2/v';

        // Placeholder filename - will be created when capture is performed
        $json = $dir . '/capture_example_cs2.json';

        // The test is a soft check: if the fixture doesn't exist yet we mark as skipped
        if (!\file_exists($json)) {
            $this->markTestSkipped('CS2 fixture not present yet. Run /bin/capture against a discovered CS2 server and save fixture.');
        }

        $this->assertFileExists($json);

        $data = \json_decode(\file_get_contents($json), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('server_info', $data);
    }
}
