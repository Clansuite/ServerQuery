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
use Clansuite\ServerQuery\ServerProtocols\UrbanTerror;
use PHPUnit\Framework\TestCase;

class UrbanTerrorTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $urbanTerror = new UrbanTerror('127.0.0.1', 27960);
        $this->assertInstanceOf(UrbanTerror::class, $urbanTerror);
    }

    public function testExtendsQuake3Arena(): void
    {
        $urbanTerror = new UrbanTerror('127.0.0.1', 27960);
        $this->assertInstanceOf(Quake3Arena::class, $urbanTerror);
    }

    public function testHasCorrectProtocol(): void
    {
        $urbanTerror = new UrbanTerror('127.0.0.1', 27960);
        $this->assertEquals('urbanterror', $urbanTerror->protocol);
    }

    public function testHasCorrectName(): void
    {
        $urbanTerror = new UrbanTerror('127.0.0.1', 27960);
        $this->assertEquals('UrbanTerror', $urbanTerror->name);
    }
}
