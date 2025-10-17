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

use Clansuite\ServerQuery\ServerProtocols\Ns2;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use PHPUnit\Framework\TestCase;

final class Ns2Test extends TestCase
{
    public function testNs2ClassExists(): void
    {
        $this->assertTrue(\class_exists(Ns2::class), 'Ns2 class should exist');
    }

    public function testNs2ExtendsSteam(): void
    {
        $reflection = new ReflectionClass(Ns2::class);
        $this->assertTrue($reflection->isSubclassOf(Steam::class), 'Ns2 should extend Steam');
    }

    public function testNs2HasCorrectProperties(): void
    {
        $server = new Ns2('127.0.0.1', 27016);
        $this->assertEquals('Natural Selection 2', $server->name);
        $this->assertEquals(['Natural Selection 2'], $server->supportedGames);
        $this->assertEquals('A2S', $server->protocol);
    }
}
