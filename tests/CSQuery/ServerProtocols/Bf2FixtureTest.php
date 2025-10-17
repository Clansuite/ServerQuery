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

use Clansuite\ServerQuery\ServerProtocols\Bf2;
use PHPUnit\Framework\TestCase;

/**
 * Test parsing of Bf2 fixture captured from live server.
 */
class Bf2FixtureTest extends TestCase
{
    private string $fixtureDir = __DIR__ . '/../../fixtures/bf2/vunknown';

    public function testFixtureExists(): void
    {
        $jsonFile = $this->fixtureDir . '/capture_144_22_214_56_16567.json';

        $this->assertFileExists($jsonFile, 'JSON fixture missing');

        $data = \json_decode(\file_get_contents($jsonFile), true);
        $this->assertIsArray($data, 'JSON fixture should decode to array');
        $this->assertArrayHasKey('server_info', $data, 'JSON fixture should contain server_info');
    }
}
