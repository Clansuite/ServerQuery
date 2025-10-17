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

use Clansuite\ServerQuery\ServerProtocols\Moh;
use Clansuite\ServerQuery\ServerProtocols\Unreal2;
use PHPUnit\Framework\TestCase;

class MohTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $moh = new Moh('127.0.0.1', 7777);
        $this->assertInstanceOf(Moh::class, $moh);
    }

    public function testExtendsUnreal2(): void
    {
        $moh = new Moh('127.0.0.1', 7777);
        $this->assertInstanceOf(Unreal2::class, $moh);
    }

    public function testHasCorrectProtocol(): void
    {
        $moh = new Moh('127.0.0.1', 7777);
        $this->assertEquals('Unreal2', $moh->protocol);
    }

    public function testHasCorrectName(): void
    {
        $moh = new Moh('127.0.0.1', 7777);
        $this->assertEquals('Medal of Honor', $moh->name);
    }
}
