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

use Clansuite\ServerQuery\ServerProtocols\Bt;
use Clansuite\ServerQuery\ServerProtocols\Quake3Arena;
use PHPUnit\Framework\TestCase;

class BtTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $bt = new Bt('127.0.0.1', 12203);
        $this->assertInstanceOf(Bt::class, $bt);
    }

    public function testExtendsQuake3Arena(): void
    {
        $bt = new Bt('127.0.0.1', 12203);
        $this->assertInstanceOf(Quake3Arena::class, $bt);
    }

    public function testHasCorrectProtocol(): void
    {
        $bt = new Bt('127.0.0.1', 12203);
        $this->assertEquals('Quake3Arena', $bt->protocol);
    }

    public function testHasCorrectName(): void
    {
        $bt = new Bt('127.0.0.1', 12203);
        $this->assertEquals('Medal of Honor Breakthrough', $bt->name);
    }
}
