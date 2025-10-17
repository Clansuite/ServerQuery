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

use Clansuite\ServerQuery\ServerProtocols\Battlefield4;
use Clansuite\ServerQuery\ServerProtocols\Mohw;
use PHPUnit\Framework\TestCase;

class MohwTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $mohw = new Mohw('127.0.0.1', 25200);
        $this->assertInstanceOf(Mohw::class, $mohw);
    }

    public function testExtendsBattlefield4(): void
    {
        $mohw = new Mohw('127.0.0.1', 25200);
        $this->assertInstanceOf(Battlefield4::class, $mohw);
    }

    public function testHasCorrectProtocol(): void
    {
        $mohw = new Mohw('127.0.0.1', 25200);
        $this->assertEquals('Battlefield4', $mohw->protocol);
    }

    public function testHasCorrectName(): void
    {
        $mohw = new Mohw('127.0.0.1', 25200);
        $this->assertEquals('Medal of Honor Warfighter', $mohw->name);
    }
}
