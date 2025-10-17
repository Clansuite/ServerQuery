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

use Clansuite\Capture\Extractor\ServerInfoExtractor;
use Clansuite\Capture\ServerInfo;
use PHPUnit\Framework\TestCase;

final class ServerInfoTest extends TestCase
{
    public function testToArrayAndDefaults(): void
    {
        $si = new ServerInfo;

        $arr = $si->toArray();

        $this->assertArrayHasKey('address', $arr);
        $this->assertArrayHasKey('numplayers', $arr);
        $this->assertSame(0, $arr['numplayers']);
    }

    public function testExtractorMapsFields(): void
    {
        $obj = (object) [
            'address'     => '1.2.3.4',
            'queryport'   => 1234,
            'online'      => true,
            'gamename'    => 'g',
            'gameversion' => 'v',
            'servertitle' => 't',
            'mapname'     => 'm',
            'gametype'    => 'gt',
            'numplayers'  => 5,
            'maxplayers'  => 16,
            'rules'       => ['a' => 'b'],
            'players'     => [['name' => 'p']],
            'errstr'      => 'err',
        ];

        $ext = new ServerInfoExtractor;
        $si  = $ext->extract($obj);

        $this->assertSame('1.2.3.4', $si->address);
        $this->assertTrue($si->online);
        $this->assertSame(5, $si->numplayers);
        $this->assertSame(['a' => 'b'], $si->rules);
    }
}
