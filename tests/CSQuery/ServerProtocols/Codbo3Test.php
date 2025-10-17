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

use Clansuite\ServerQuery\ServerProtocols\Codbo3;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use PHPUnit\Framework\TestCase;

class Codbo3Test extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $codbo3 = new Codbo3('127.0.0.1', 27015);
        $this->assertInstanceOf(Codbo3::class, $codbo3);
    }

    public function testExtendsSteam(): void
    {
        $codbo3 = new Codbo3('127.0.0.1', 27015);
        $this->assertInstanceOf(Steam::class, $codbo3);
    }

    public function testHasCorrectProtocol(): void
    {
        $codbo3 = new Codbo3('127.0.0.1', 27015);
        $this->assertEquals('A2S', $codbo3->protocol);
    }

    public function testHasCorrectName(): void
    {
        $codbo3 = new Codbo3('127.0.0.1', 27015);
        $this->assertEquals('Call of Duty: Black Ops 3', $codbo3->name);
    }
}
