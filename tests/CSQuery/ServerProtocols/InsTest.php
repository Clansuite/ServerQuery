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

use Clansuite\ServerQuery\ServerProtocols\Ins;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use PHPUnit\Framework\TestCase;

final class InsTest extends TestCase
{
    public function testInsClassExists(): void
    {
        $this->assertTrue(\class_exists(Ins::class), 'Ins class should exist');
    }

    public function testInsExtendsSteam(): void
    {
        $reflection = new ReflectionClass(Ins::class);
        $this->assertTrue($reflection->isSubclassOf(Steam::class), 'Ins should extend Steam');
    }
}
