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

use Clansuite\ServerQuery\ServerProtocols\Dayz;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use PHPUnit\Framework\TestCase;

final class DayzTest extends TestCase
{
    public function testDayzClassExists(): void
    {
        $this->assertTrue(\class_exists(Dayz::class), 'Dayz class should exist');
    }

    public function testDayzExtendsSteam(): void
    {
        $reflection = new ReflectionClass(Dayz::class);
        $this->assertTrue($reflection->isSubclassOf(Steam::class), 'Dayz should extend Steam');
    }
}
