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

use Clansuite\ServerQuery\ServerProtocols\Unreal2;
use Clansuite\ServerQuery\ServerProtocols\Ut2k4;
use PHPUnit\Framework\TestCase;

class Ut2k4Test extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $ut2k4 = new Ut2k4('127.0.0.1', 7777);
        $this->assertInstanceOf(Ut2k4::class, $ut2k4);
    }

    public function testExtendsUnreal2(): void
    {
        $ut2k4 = new Ut2k4('127.0.0.1', 7777);
        $this->assertInstanceOf(Unreal2::class, $ut2k4);
    }

    public function testHasCorrectProtocol(): void
    {
        $ut2k4 = new Ut2k4('127.0.0.1', 7777);
        $this->assertEquals('Unreal2', $ut2k4->protocol);
    }

    public function testHasCorrectName(): void
    {
        $ut2k4 = new Ut2k4('127.0.0.1', 7777);
        $this->assertEquals('Unreal Tournament 2004', $ut2k4->name);
    }
}
