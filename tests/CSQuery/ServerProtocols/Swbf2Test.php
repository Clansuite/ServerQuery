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

use Clansuite\ServerQuery\ServerProtocols\Battlefield4;
use Clansuite\ServerQuery\ServerProtocols\Swbf2;
use PHPUnit\Framework\TestCase;

class Swbf2Test extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $swbf2 = new Swbf2('127.0.0.1', 25200);
        $this->assertInstanceOf(Swbf2::class, $swbf2);
    }

    public function testExtendsBattlefield4(): void
    {
        $swbf2 = new Swbf2('127.0.0.1', 25200);
        $this->assertInstanceOf(Battlefield4::class, $swbf2);
    }

    public function testHasCorrectProtocol(): void
    {
        $swbf2 = new Swbf2('127.0.0.1', 25200);
        $this->assertEquals('BF4', $swbf2->protocol);
    }

    public function testHasCorrectName(): void
    {
        $swbf2 = new Swbf2('127.0.0.1', 25200);
        $this->assertEquals('Star Wars Battlefront 2', $swbf2->name);
    }
}
