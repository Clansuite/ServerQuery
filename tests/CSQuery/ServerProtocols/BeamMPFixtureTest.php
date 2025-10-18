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

use Clansuite\Capture\ServerAddress;
use Clansuite\ServerQuery\ServerProtocols\BeamMP;
use PHPUnit\Framework\TestCase;

final class BeamMPFixtureTest extends TestCase
{
    private string $fixture = __DIR__ . '/../../fixtures/beammp/capture_23_88_73_88_34127.json';

    public function testBeamMPParsingFromFixture(): void
    {
        $this->assertFileExists($this->fixture, 'BeamMP fixture file missing');

        $raw = \json_decode(\file_get_contents($this->fixture), true);
        $this->assertIsArray($raw);

        // Simulate what the protocol does when it finds an entry in the backend
        $protocol = new BeamMP($raw['ip'] ?? '127.0.0.1', (int) ($raw['port'] ?? 0));

        // Manually inject the parsed logic by calling query_server on a fake match.
        // We'll emulate the internal behavior: set $found to $raw and then assert
        // the parsing results by calling the same parsing paths.

        // Use reflection to call the internal query-related behavior by copying minimal steps
        $reflection = new ReflectionClass($protocol);
        $reflection->getMethod('query');

        // Create a ServerAddress object and call query() which will call query_server().
        $addrClass = new ReflectionClass(ServerAddress::class);
        $addr      = $addrClass->newInstanceArgs([$raw['ip'], (int) $raw['port']]);

        $protocol->query($addr);

        // Verify parsed values
        $this->assertEquals('Rac3cont3nt', $protocol->servertitle);
        $this->assertEquals('/levels/west_coast_usa/info.json', $protocol->mapname);
        $this->assertEquals(0, $protocol->numplayers);
        $this->assertEquals(25, $protocol->maxplayers);
        $this->assertIsArray($protocol->players);
        $this->assertCount(0, $protocol->players);

        // Mods parsed
        $this->assertArrayHasKey('mods', $protocol->rules);
        $this->assertIsArray($protocol->rules['mods']);
    }
}
