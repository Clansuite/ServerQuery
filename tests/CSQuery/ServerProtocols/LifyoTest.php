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

use Clansuite\ServerQuery\ServerProtocols\Lifyo;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use PHPUnit\Framework\TestCase;

class LifyoTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $lifyo = new Lifyo('127.0.0.1', 28000);
        $this->assertInstanceOf(Lifyo::class, $lifyo);
    }

    public function testExtendsSteam(): void
    {
        $lifyo = new Lifyo('127.0.0.1', 28000);
        $this->assertInstanceOf(Steam::class, $lifyo);
    }

    public function testHasCorrectProtocol(): void
    {
        $lifyo = new Lifyo('127.0.0.1', 28000);
        $this->assertEquals('A2S', $lifyo->protocol);
    }

    public function testHasCorrectName(): void
    {
        $lifyo = new Lifyo('127.0.0.1', 28000);
        $this->assertEquals('Life Is Feudal', $lifyo->name);
    }
}
