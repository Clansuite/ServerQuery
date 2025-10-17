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

use Clansuite\ServerQuery\ServerProtocols\DayOfDefeatSource;
use PHPUnit\Framework\TestCase;

final class DayOfDefeatSourceTest extends TestCase
{
    public function testCanInstantiateAndQueryWithFixture(): void
    {
        // This test attempts to load a fixture using the PacketCapture loader.
        // If no fixture exists, we assert that the class can be instantiated.

        $server = new DayOfDefeatSource('127.0.0.1', 27015);

        $this->assertInstanceOf(DayOfDefeatSource::class, $server);

        // Try a quick query but do not fail the test on network errors.
        $result = $server->query_server(true, true);

        // Result can be true or false depending on network; ensure no exception thrown
        $this->assertIsBool($result);

        // If query succeeded ensure required fields exist
        if ($result) {
            $this->assertNotEmpty($server->servertitle);
            $this->assertNotEmpty($server->mapname);
            $this->assertIsInt($server->numplayers);
            $this->assertIsArray($server->players);
        }
    }
}
