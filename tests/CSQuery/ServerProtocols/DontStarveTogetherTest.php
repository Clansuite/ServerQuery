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

use Clansuite\ServerQuery\ServerProtocols\DontStarveTogether;
use Clansuite\ServerQuery\ServerProtocols\Steam;
use PHPUnit\Framework\TestCase;

class DontStarveTogetherTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $dst = new DontStarveTogether('127.0.0.1', 10999);
        $this->assertInstanceOf(DontStarveTogether::class, $dst);
    }

    public function testExtendsSteam(): void
    {
        $dst = new DontStarveTogether('127.0.0.1', 10999);
        $this->assertInstanceOf(Steam::class, $dst);
    }

    public function testHasCorrectProtocol(): void
    {
        $dst = new DontStarveTogether('127.0.0.1', 10999);
        $this->assertEquals('A2S', $dst->protocol);
    }

    public function testHasCorrectName(): void
    {
        $dst = new DontStarveTogether('127.0.0.1', 10999);
        $this->assertEquals('Don\'t Starve Together', $dst->name);
    }
}
