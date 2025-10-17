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

use Clansuite\ServerQuery\ServerProtocols\Steam;
use Clansuite\ServerQuery\ServerProtocols\Tf2;
use PHPUnit\Framework\TestCase;

final class Tf2Test extends TestCase
{
    public function testTf2ClassExists(): void
    {
        $this->assertTrue(\class_exists(Tf2::class), 'Tf2 class should exist');
    }

    public function testTf2ExtendsSteam(): void
    {
        $reflection = new ReflectionClass(Tf2::class);
        $this->assertTrue($reflection->isSubclassOf(Steam::class), 'Tf2 should extend Steam');
    }
}
