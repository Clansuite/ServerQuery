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

use Clansuite\ServerQuery\ServerProtocols\Crysis;
use Clansuite\ServerQuery\ServerProtocols\Gamespy3;
use PHPUnit\Framework\TestCase;

class CrysisTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $crysis = new Crysis('127.0.0.1', 64087);
        $this->assertInstanceOf(Crysis::class, $crysis);
    }

    public function testExtendsGamespy3(): void
    {
        $crysis = new Crysis('127.0.0.1', 64087);
        $this->assertInstanceOf(Gamespy3::class, $crysis);
    }

    public function testHasCorrectProtocol(): void
    {
        $crysis = new Crysis('127.0.0.1', 64087);
        $this->assertEquals('Gamespy3', $crysis->protocol);
    }
}
