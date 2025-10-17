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

use Clansuite\ServerQuery\ServerProtocols\Crysis2;
use Clansuite\ServerQuery\ServerProtocols\Gamespy3;
use PHPUnit\Framework\TestCase;

class Crysis2Test extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $crysis2 = new Crysis2('127.0.0.1', 64087);
        $this->assertInstanceOf(Crysis2::class, $crysis2);
    }

    public function testExtendsGamespy3(): void
    {
        $crysis2 = new Crysis2('127.0.0.1', 64087);
        $this->assertInstanceOf(Gamespy3::class, $crysis2);
    }

    public function testHasCorrectProtocol(): void
    {
        $crysis2 = new Crysis2('127.0.0.1', 64087);
        $this->assertEquals('Gamespy3', $crysis2->protocol);
    }

    public function testHasCorrectName(): void
    {
        $crysis2 = new Crysis2('127.0.0.1', 64087);
        $this->assertEquals('Crysis 2', $crysis2->name);
    }
}
