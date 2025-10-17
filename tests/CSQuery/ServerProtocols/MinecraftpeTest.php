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

use Clansuite\ServerQuery\ServerProtocols\Minecraft;
use Clansuite\ServerQuery\ServerProtocols\Minecraftpe;
use PHPUnit\Framework\TestCase;

class MinecraftpeTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $minecraftpe = new Minecraftpe('127.0.0.1', 19132);
        $this->assertInstanceOf(Minecraftpe::class, $minecraftpe);
    }

    public function testExtendsMinecraft(): void
    {
        $minecraftpe = new Minecraftpe('127.0.0.1', 19132);
        $this->assertInstanceOf(Minecraft::class, $minecraftpe);
    }

    public function testHasCorrectProtocol(): void
    {
        $minecraftpe = new Minecraftpe('127.0.0.1', 19132);
        $this->assertEquals('minecraft', $minecraftpe->protocol);
    }

    public function testHasCorrectName(): void
    {
        $minecraftpe = new Minecraftpe('127.0.0.1', 19132);
        $this->assertEquals('Minecraft Pocket Edition', $minecraftpe->name);
    }
}
