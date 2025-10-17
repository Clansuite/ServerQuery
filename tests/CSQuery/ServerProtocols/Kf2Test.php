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

use Clansuite\ServerQuery\ServerProtocols\Kf2;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use PHPUnit\Framework\TestCase;

final class Kf2Test extends TestCase
{
    public function testKf2ClassExists(): void
    {
        $this->assertTrue(\class_exists(Kf2::class), 'Kf2 class should exist');
    }

    public function testKf2ExtendsSteam(): void
    {
        $reflection = new ReflectionClass(Kf2::class);
        $this->assertTrue($reflection->isSubclassOf(Steam::class), 'Kf2 should extend Steam');
    }
}
