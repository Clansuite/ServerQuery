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

use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\Protocol\ProtocolResolver;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\Capture\UnknownProtocolException;
use PHPUnit\Framework\TestCase;

final class ProtocolResolverTest extends TestCase
{
    public function testResolveKnown(): void
    {
        $anon = new class implements ProtocolInterface
        {
            public function query(ServerAddress $addr): ServerInfo
            {
                return new ServerInfo;
            }

            public function getProtocolName(): string
            {
                return 'anon';
            }

            public function getVersion(ServerInfo $info): string
            {
                return 'v';
            }
        };

        $map = ['source' => $anon::class];
        $r   = new ProtocolResolver($map);

        $obj = $r->resolve('source', '1.2.3.4', 1234);

        $this->assertInstanceOf(ProtocolInterface::class, $obj);
    }

    public function testResolveUnknownThrows(): void
    {
        $this->expectException(UnknownProtocolException::class);

        $r = new ProtocolResolver([]);
        $r->resolve('nope', '1.2.3.4', 1234);
    }
}
