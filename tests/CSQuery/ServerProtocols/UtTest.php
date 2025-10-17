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

use Clansuite\ServerQuery\ServerProtocols\Gamespy;
use Clansuite\ServerQuery\ServerProtocols\Ut;
use PHPUnit\Framework\TestCase;

final class UtTest extends TestCase
{
    public function testUtClassExists(): void
    {
        $this->assertTrue(\class_exists(Ut::class), 'Ut class should exist');
    }

    public function testUtExtendsGamespy(): void
    {
        $reflection = new ReflectionClass(Ut::class);
        $this->assertTrue($reflection->isSubclassOf(Gamespy::class), 'Ut should extend Gamespy');
    }

    public function testUtHasCorrectProperties(): void
    {
        $server = new Ut('127.0.0.1', 7778);
        $this->assertEquals('Unreal Tournament', $server->name);
        $this->assertEquals(['Unreal Tournament'], $server->supportedGames);
        $this->assertEquals('Gamespy', $server->protocol);
    }
}
