<?php declare(strict_types=1);

/**
 * Clansuite Server Query
 *
 * SPDX-FileCopyrightText: 2003-2025 Jens A. Koch
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\CSQuery\ServerProtocols;

use Clansuite\ServerQuery\CSQuery;
use Clansuite\ServerQuery\ServerProtocols\CounterStrike16;
use PHPUnit\Framework\TestCase;

class CzeroTest extends TestCase
{
    public function testCreateInstanceReturnsCounterStrike16ForCzero(): void
    {
        $factory = new CSQuery;

        $instance = $factory->createInstance('Czero', '127.0.0.1', 27015);
        $this->assertInstanceOf(CounterStrike16::class, $instance);

        $instanceLower = $factory->createInstance('czero', '127.0.0.1', 27015);
        $this->assertInstanceOf(CounterStrike16::class, $instanceLower);
    }
}
