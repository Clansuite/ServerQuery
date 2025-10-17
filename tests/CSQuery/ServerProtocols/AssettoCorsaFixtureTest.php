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

use Clansuite\ServerQuery\ServerProtocols\AssettoCorsa;
use PHPUnit\Framework\TestCase;

final class AssettoCorsaFixtureTest extends TestCase
{
    public function testAssettoCorsaProtocolCanBeInstantiated(): void
    {
        $protocol = new AssettoCorsa('127.0.0.1', 9600);
        $this->assertInstanceOf(AssettoCorsa::class, $protocol);
        $this->assertEquals('assettocorsa', $protocol->getProtocolName());
    }

    public function testAssettoCorsaQueryReturnsExpectedStructure(): void
    {
        $protocol = new AssettoCorsa('5.161.43.117', 8081);
        $result   = $protocol->query_server(true, true);

        $this->assertIsBool($result);

        if ($result) {
            $this->assertIsString($protocol->servertitle);
            $this->assertIsString($protocol->mapname);
            $this->assertIsInt($protocol->numplayers);
            $this->assertIsInt($protocol->maxplayers);
            $this->assertIsArray($protocol->players);
        }
    }
}
