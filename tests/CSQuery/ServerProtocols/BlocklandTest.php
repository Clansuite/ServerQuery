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

use Clansuite\ServerQuery\ServerProtocols\Blockland;
use Clansuite\ServerQuery\ServerProtocols\Tribes2;
use PHPUnit\Framework\TestCase;

class BlocklandTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $blockland = new Blockland('127.0.0.1', 28000);
        $this->assertInstanceOf(Blockland::class, $blockland);
    }

    public function testExtendsTribes2(): void
    {
        $blockland = new Blockland('127.0.0.1', 28000);
        $this->assertInstanceOf(Tribes2::class, $blockland);
    }

    public function testHasCorrectProtocol(): void
    {
        $blockland = new Blockland('127.0.0.1', 28000);
        $this->assertEquals('Blockland', $blockland->protocol);
    }

    public function testHasCorrectName(): void
    {
        $blockland = new Blockland('127.0.0.1', 28000);
        $this->assertEquals('Blockland', $blockland->name);
    }
}
