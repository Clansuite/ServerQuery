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

use Clansuite\ServerQuery\ServerProtocols\Rordh;
use Clansuite\ServerQuery\ServerProtocols\Unreal2;
use PHPUnit\Framework\TestCase;

class RordhTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $rordh = new Rordh('127.0.0.1', 7777);
        $this->assertInstanceOf(Rordh::class, $rordh);
    }

    public function testExtendsUnreal2(): void
    {
        $rordh = new Rordh('127.0.0.1', 7777);
        $this->assertInstanceOf(Unreal2::class, $rordh);
    }

    public function testHasCorrectProtocol(): void
    {
        $rordh = new Rordh('127.0.0.1', 7777);
        $this->assertEquals('Unreal2', $rordh->protocol);
    }

    public function testHasCorrectName(): void
    {
        $rordh = new Rordh('127.0.0.1', 7777);
        $this->assertEquals('Darkest Hour', $rordh->name);
    }
}
