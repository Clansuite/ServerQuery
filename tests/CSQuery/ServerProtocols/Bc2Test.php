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
use Clansuite\ServerQuery\ServerProtocols\Bc2;
use PHPUnit\Framework\TestCase;

final class Bc2Test extends TestCase
{
    public function testBc2ClassExists(): void
    {
        $this->assertTrue(\class_exists(Bc2::class), 'Bc2 class should exist');
    }

    public function testBc2ExtendsCSQuery(): void
    {
        $reflection = new ReflectionClass(Bc2::class);
        $this->assertTrue($reflection->isSubclassOf(CSQuery::class), 'Bc2 should extend CSQuery');
    }
}
