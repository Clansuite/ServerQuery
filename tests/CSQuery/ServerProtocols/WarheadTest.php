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

use Clansuite\ServerQuery\ServerProtocols\Gamespy3;
use Clansuite\ServerQuery\ServerProtocols\Warhead;
use PHPUnit\Framework\TestCase;

class WarheadTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $warhead = new Warhead('127.0.0.1', 64087);
        $this->assertInstanceOf(Warhead::class, $warhead);
    }

    public function testExtendsGamespy3(): void
    {
        $warhead = new Warhead('127.0.0.1', 64087);
        $this->assertInstanceOf(Gamespy3::class, $warhead);
    }

    public function testHasCorrectProtocol(): void
    {
        $warhead = new Warhead('127.0.0.1', 64087);
        $this->assertEquals('Gamespy3', $warhead->protocol);
    }

    public function testHasCorrectName(): void
    {
        $warhead = new Warhead('127.0.0.1', 64087);
        $this->assertEquals('Crysis Wars', $warhead->name);
    }
}
