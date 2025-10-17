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

use Clansuite\ServerQuery\ServerProtocols\Torque;
use Clansuite\ServerQuery\ServerProtocols\Tribes2;
use PHPUnit\Framework\TestCase;

class Tribes2Test extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $tribes2 = new Tribes2('127.0.0.1', 28000);
        $this->assertInstanceOf(Tribes2::class, $tribes2);
    }

    public function testExtendsTorque(): void
    {
        $tribes2 = new Tribes2('127.0.0.1', 28000);
        $this->assertInstanceOf(Torque::class, $tribes2);
    }

    public function testHasCorrectProtocol(): void
    {
        $tribes2 = new Tribes2('127.0.0.1', 28000);
        $this->assertEquals('Tribes2', $tribes2->protocol);
    }

    public function testHasCorrectName(): void
    {
        $tribes2 = new Tribes2('127.0.0.1', 28000);
        $this->assertEquals('Tribes 2', $tribes2->name);
    }
}
