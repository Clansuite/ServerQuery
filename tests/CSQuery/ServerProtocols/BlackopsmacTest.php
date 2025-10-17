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

use Clansuite\ServerQuery\ServerProtocols\Blackopsmac;
use Clansuite\ServerQuery\ServerProtocols\Quake3Arena;
use PHPUnit\Framework\TestCase;

class BlackopsmacTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $blackopsmac = new Blackopsmac('127.0.0.1', 28960);
        $this->assertInstanceOf(Blackopsmac::class, $blackopsmac);
    }

    public function testExtendsQuake3Arena(): void
    {
        $blackopsmac = new Blackopsmac('127.0.0.1', 28960);
        $this->assertInstanceOf(Quake3Arena::class, $blackopsmac);
    }

    public function testHasCorrectProtocol(): void
    {
        $blackopsmac = new Blackopsmac('127.0.0.1', 28960);
        $this->assertEquals('Quake3', $blackopsmac->protocol);
    }

    public function testHasCorrectName(): void
    {
        $blackopsmac = new Blackopsmac('127.0.0.1', 28960);
        $this->assertEquals('Call of Duty: Black Ops Mac', $blackopsmac->name);
    }
}
