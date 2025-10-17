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

use Clansuite\ServerQuery\ServerProtocols\Ventrilo;
use PHPUnit\Framework\TestCase;

final class VentriloFixtureReplayTest extends TestCase
{
    public function testFixtureJsonLoads(): void
    {
        $path = __DIR__ . '/../../fixtures/ventrilo/capture_78_129_193_68_3808.json';
        $this->assertFileExists($path, 'Fixture file should exist');

        $json = \file_get_contents($path);
        $this->assertNotFalse($json, 'Should be able to read fixture');

        $data = \json_decode($json, true);
        $this->assertIsArray($data, 'Fixture should decode to array');

        // Basic structure assertions
        $this->assertArrayHasKey('78.129.193.68:3808', $data);
        $entry = $data['78.129.193.68:3808'];
        $this->assertArrayHasKey('players', $entry);
        $this->assertIsArray($entry['players']);

        // The class should be instantiable
        $proto = new Ventrilo('78.129.193.68', 3808);
        $this->assertInstanceOf(Ventrilo::class, $proto);
    }
}
