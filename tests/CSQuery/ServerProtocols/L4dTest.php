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

use Clansuite\ServerQuery\ServerProtocols\L4d;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use PHPUnit\Framework\TestCase;

final class L4dTest extends TestCase
{
    public function testL4dClassExists(): void
    {
        $this->assertTrue(\class_exists(L4d::class), 'L4d class should exist');
    }

    public function testL4dExtendsSteam(): void
    {
        $reflection = new ReflectionClass(L4d::class);
        $this->assertTrue($reflection->isSubclassOf(Steam::class), 'L4d should extend Steam');
    }
}
