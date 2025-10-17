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

use Clansuite\ServerQuery\ServerProtocols\Cod4;
use Clansuite\ServerQuery\ServerProtocols\Quake3Arena;
use PHPUnit\Framework\TestCase;

final class Cod4Test extends TestCase
{
    public function testCod4ClassExists(): void
    {
        $this->assertTrue(\class_exists(Cod4::class), 'Cod4 class should exist');
    }

    public function testCod4ExtendsQuake3Arena(): void
    {
        $reflection = new ReflectionClass(Cod4::class);
        $this->assertTrue($reflection->isSubclassOf(Quake3Arena::class), 'Cod4 should extend Quake3Arena');
    }
}
