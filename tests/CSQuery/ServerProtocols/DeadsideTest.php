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

use Clansuite\ServerQuery\ServerProtocols\Deadside;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use PHPUnit\Framework\TestCase;

class DeadsideTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $deadside = new Deadside('127.0.0.1', 27015);
        $this->assertInstanceOf(Deadside::class, $deadside);
    }

    public function testExtendsSteam(): void
    {
        $deadside = new Deadside('127.0.0.1', 27015);
        $this->assertInstanceOf(Steam::class, $deadside);
    }

    public function testHasCorrectProtocol(): void
    {
        $deadside = new Deadside('127.0.0.1', 27015);
        $this->assertEquals('A2S', $deadside->protocol);
    }

    public function testHasCorrectName(): void
    {
        $deadside = new Deadside('127.0.0.1', 27015);
        $this->assertEquals('DEADSIDE', $deadside->name);
    }
}
