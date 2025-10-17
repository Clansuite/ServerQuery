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

use Clansuite\ServerQuery\ServerProtocols\Dayzmod;
use Clansuite\ServerQuery\ServerProtocols\Gamespy2;
use PHPUnit\Framework\TestCase;

class DayzmodTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $dayzmod = new Dayzmod('127.0.0.1', 2302);
        $this->assertInstanceOf(Dayzmod::class, $dayzmod);
    }

    public function testExtendsGamespy2(): void
    {
        $dayzmod = new Dayzmod('127.0.0.1', 2302);
        $this->assertInstanceOf(Gamespy2::class, $dayzmod);
    }

    public function testHasCorrectProtocol(): void
    {
        $dayzmod = new Dayzmod('127.0.0.1', 2302);
        $this->assertEquals('Gamespy2', $dayzmod->protocol);
    }

    public function testHasCorrectName(): void
    {
        $dayzmod = new Dayzmod('127.0.0.1', 2302);
        $this->assertEquals('DayZ Mod', $dayzmod->name);
    }
}
