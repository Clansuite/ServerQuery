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

use Clansuite\ServerQuery\ServerProtocols\Quake3Arena;
use Clansuite\ServerQuery\ServerProtocols\StarWarsJK;
use PHPUnit\Framework\TestCase;

class StarWarsJKTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $starWarsJK = new StarWarsJK('127.0.0.1', 29070);
        $this->assertInstanceOf(StarWarsJK::class, $starWarsJK);
    }

    public function testExtendsQuake3Arena(): void
    {
        $starWarsJK = new StarWarsJK('127.0.0.1', 29070);
        $this->assertInstanceOf(Quake3Arena::class, $starWarsJK);
    }

    public function testHasCorrectProtocol(): void
    {
        $starWarsJK = new StarWarsJK('127.0.0.1', 29070);
        $this->assertEquals('StarWarsJK', $starWarsJK->protocol);
    }

    public function testHasCorrectName(): void
    {
        $starWarsJK = new StarWarsJK('127.0.0.1', 29070);
        $this->assertEquals('StarWarsJK', $starWarsJK->name);
    }
}
