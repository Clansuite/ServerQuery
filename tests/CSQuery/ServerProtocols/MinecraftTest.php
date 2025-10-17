<?php

declare(strict_types=1);

/**
 * Clansuite Server Query
 *
 * SPDX-FileCopyrightText: 2003-2025 Jens A. Koch
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Clansuite\ServerQuery\CSQuery;
use Clansuite\ServerQuery\ServerProtocols\Minecraft;
use PHPUnit\Framework\TestCase;

final class MinecraftTest extends TestCase
{
    public function testMinecraftClassExists(): void
    {
        $this->assertTrue(\class_exists(Minecraft::class), 'Minecraft class should exist');
    }

    public function testMinecraftExtendsCSQuery(): void
    {
        $reflection = new ReflectionClass(Minecraft::class);
        $this->assertTrue($reflection->isSubclassOf(CSQuery::class), 'Minecraft should extend CSQuery');
    }
}
