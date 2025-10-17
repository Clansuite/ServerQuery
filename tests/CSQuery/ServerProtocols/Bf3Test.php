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
use Clansuite\ServerQuery\ServerProtocols\Bf3;
use PHPUnit\Framework\TestCase;

final class Bf3Test extends TestCase
{
    public function testBf3ClassExists(): void
    {
        $this->assertTrue(\class_exists(Bf3::class), 'Bf3 class should exist');
    }

    public function testBf3ExtendsCSQuery(): void
    {
        $reflection = new ReflectionClass(Bf3::class);
        $this->assertTrue($reflection->isSubclassOf(CSQuery::class), 'Bf3 should extend CSQuery');
    }
}
