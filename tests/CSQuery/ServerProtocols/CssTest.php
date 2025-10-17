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

use Clansuite\ServerQuery\ServerProtocols\CounterStrikeSource;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use PHPUnit\Framework\TestCase;

final class CssTest extends TestCase
{
    public function testCssClassExists(): void
    {
        $this->assertTrue(\class_exists(CounterStrikeSource::class), 'CounterStrikeSource class should exist');
    }

    public function testCssExtendsSteam(): void
    {
        $reflection = new ReflectionClass(CounterStrikeSource::class);
        $this->assertTrue($reflection->isSubclassOf(Steam::class), 'CounterStrikeSource should extend Steam');
    }
}
