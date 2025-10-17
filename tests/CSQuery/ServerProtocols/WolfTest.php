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

use Clansuite\ServerQuery\ServerProtocols\Quake3Arena;
use Clansuite\ServerQuery\ServerProtocols\Wolf;
use PHPUnit\Framework\TestCase;

class WolfTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $wolf = new Wolf('127.0.0.1', 27960);
        $this->assertInstanceOf(Wolf::class, $wolf);
    }

    public function testExtendsQuake3Arena(): void
    {
        $wolf = new Wolf('127.0.0.1', 27960);
        $this->assertInstanceOf(Quake3Arena::class, $wolf);
    }

    public function testHasCorrectProtocol(): void
    {
        $wolf = new Wolf('127.0.0.1', 27960);
        $this->assertEquals('wolf', $wolf->protocol);
    }

    public function testHasCorrectName(): void
    {
        $wolf = new Wolf('127.0.0.1', 27960);
        $this->assertEquals('Wolf', $wolf->name);
    }
}
