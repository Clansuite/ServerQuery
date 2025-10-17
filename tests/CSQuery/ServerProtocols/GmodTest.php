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

use Clansuite\ServerQuery\ServerProtocols\Gmod;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use PHPUnit\Framework\TestCase;

final class GmodTest extends TestCase
{
    public function testGmodClassExists(): void
    {
        $this->assertTrue(\class_exists(Gmod::class), 'Gmod class should exist');
    }

    public function testGmodExtendsSteam(): void
    {
        $reflection = new ReflectionClass(Gmod::class);
        $this->assertTrue($reflection->isSubclassOf(Steam::class), 'Gmod should extend Steam');
    }
}
