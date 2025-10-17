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

use Clansuite\ServerQuery\ServerProtocols\Hl2zp;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use PHPUnit\Framework\TestCase;

final class Hl2zpTest extends TestCase
{
    public function testHl2zpClassExists(): void
    {
        $this->assertTrue(\class_exists(Hl2zp::class), 'Hl2zp class should exist');
    }

    public function testHl2zpExtendsSteam(): void
    {
        $reflection = new ReflectionClass(Hl2zp::class);
        $this->assertTrue($reflection->isSubclassOf(Steam::class), 'Hl2zp should extend Steam');
    }
}
