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

use Clansuite\ServerQuery\ServerProtocols\Squad;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use PHPUnit\Framework\TestCase;

class SquadTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $squad = new Squad('127.0.0.1', 7787);
        $this->assertInstanceOf(Squad::class, $squad);
    }

    public function testExtendsSteam(): void
    {
        $squad = new Squad('127.0.0.1', 7787);
        $this->assertInstanceOf(Steam::class, $squad);
    }

    public function testHasCorrectProtocol(): void
    {
        $squad = new Squad('127.0.0.1', 7787);
        $this->assertEquals('A2S', $squad->protocol);
    }

    public function testHasCorrectName(): void
    {
        $squad = new Squad('127.0.0.1', 7787);
        $this->assertEquals('SQUAD', $squad->name);
    }
}
