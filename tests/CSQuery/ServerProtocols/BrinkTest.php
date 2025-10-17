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

use Clansuite\ServerQuery\ServerProtocols\Brink;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use PHPUnit\Framework\TestCase;

class BrinkTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $brink = new Brink('127.0.0.1', 27015);
        $this->assertInstanceOf(Brink::class, $brink);
    }

    public function testExtendsSteam(): void
    {
        $brink = new Brink('127.0.0.1', 27015);
        $this->assertInstanceOf(Steam::class, $brink);
    }

    public function testHasCorrectProtocol(): void
    {
        $brink = new Brink('127.0.0.1', 27015);
        $this->assertEquals('A2S', $brink->protocol);
    }

    public function testHasCorrectName(): void
    {
        $brink = new Brink('127.0.0.1', 27015);
        $this->assertEquals('Brink', $brink->name);
    }
}
