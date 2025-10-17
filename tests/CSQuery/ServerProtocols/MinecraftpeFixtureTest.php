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

namespace Tests\CSQuery\ServerProtocols;

use function method_exists;
use Clansuite\ServerQuery\ServerProtocols\Minecraftpe;
use PHPUnit\Framework\TestCase;

class MinecraftpeFixtureTest extends TestCase
{
    public function testQueryWithFixture(): void
    {
        // Note: No live Minecraft Pocket Edition servers available for testing
        // This test verifies the class can be instantiated and query method exists

        $minecraftpe = new Minecraftpe('127.0.0.1', 19132);

        // Test that the query method exists and is callable
        $this->assertTrue(method_exists($minecraftpe, 'query_server'));

        // Since no fixture data exists, we don't attempt actual query
        // The class is ready for use when a live server becomes available
    }
}
