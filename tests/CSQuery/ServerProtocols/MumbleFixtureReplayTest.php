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

use Clansuite\ServerQuery\ServerProtocols\Mumble;
use PHPUnit\Framework\TestCase;

final class MumbleFixtureReplayTest extends TestCase
{
    public function testFixtureJsonLoads(): void
    {
        $path = __DIR__ . '/../../fixtures/mumble/capture_95_130_64_232_64738.json';
        $this->assertFileExists($path, 'Fixture file should exist');

        $json = \file_get_contents($path);
        $this->assertNotFalse($json, 'Should be able to read fixture');

        $data = \json_decode($json, true);
        $this->assertIsArray($data, 'Fixture should decode to array');

        $this->assertArrayHasKey('95.130.64.232:64738', $data);
        $entry = $data['95.130.64.232:64738'];
        $this->assertArrayHasKey('players', $entry);
        $this->assertIsArray($entry['players']);

        $proto = new Mumble('95.130.64.232', 64738);
        $this->assertInstanceOf(Mumble::class, $proto);
    }
}
