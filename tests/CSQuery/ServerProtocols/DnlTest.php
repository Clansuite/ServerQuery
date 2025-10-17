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

use Clansuite\ServerQuery\ServerProtocols\Dnl;
use Clansuite\ServerQuery\ServerProtocols\Unreal2;
use PHPUnit\Framework\TestCase;

class DnlTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $dnl = new Dnl('127.0.0.1', 7777);
        $this->assertInstanceOf(Dnl::class, $dnl);
    }

    public function testExtendsUnreal2(): void
    {
        $dnl = new Dnl('127.0.0.1', 7777);
        $this->assertInstanceOf(Unreal2::class, $dnl);
    }

    public function testHasCorrectProtocol(): void
    {
        $dnl = new Dnl('127.0.0.1', 7777);
        $this->assertEquals('Unreal2', $dnl->protocol);
    }

    public function testHasCorrectName(): void
    {
        $dnl = new Dnl('127.0.0.1', 7777);
        $this->assertEquals('Dark and Light', $dnl->name);
    }
}
