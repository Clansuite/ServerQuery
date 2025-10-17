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

use Clansuite\Capture\ServerAddress;
use Clansuite\ServerQuery\ServerProtocols\Ase;
use PHPUnit\Framework\TestCase;

final class AseTest extends TestCase
{
    public function testReadLengthPrefixedStringEdgeCases(): void
    {
        $ase = new Ase;

        $rm = new ReflectionMethod(Ase::class, 'readLengthPrefixedString');
        $rm->setAccessible(true);

        $buffer = '';
        // invokeArgs allows passing by reference
        $this->assertSame('', $rm->invokeArgs($ase, [&$buffer]));

        // build buffer where length byte indicates longer than available
        $buf = \chr(10) . 'abc';
        $b   = $buf;
        $out = $rm->invokeArgs($ase, [&$b]);
        $this->assertSame('abc', $out);
    }

    public function testQueryServerParsesHeaderRulesAndPlayers(): void
    {
        // Build an ASE response payload
        // Header: 'EYE1'
        $payload = 'EYE1';

        // helper to append length-prefixed string (length = content length + 1)
        $lp = static function (string $s)
        {
            return \chr(\strlen($s) + 1) . $s;
        };

        $payload .= $lp('GameName');
        $payload .= $lp('7777'); // port
        $payload .= $lp('ServerName');
        $payload .= $lp('GameType');
        $payload .= $lp('MapName');
        $payload .= $lp('1.0');
        $payload .= $lp('0'); // password
        $payload .= $lp('1'); // num_players
        $payload .= $lp('16'); // max_players

        // Add a custom key/value pair and terminating '0' key
        $payload .= $lp('custom_key') . $lp('custom_val');
        $payload .= $lp('0');

        // Players: append a player with flags set for name(1) and score(8)
        // flags byte
        $player = \chr(1 | 8);
        // name
        $player .= $lp('PlayerOne');
        // score
        $player .= $lp('42');

        $payload .= $player;

        // Create a stubbed Ase that returns the payload from sendCommand
        $ase = new class($payload) extends Ase
        {
            private string $resp;

            public function __construct(string $resp)
            {
                parent::__construct();
                $this->resp = $resp;
            }

            protected function sendCommand(string $address, int $port, string $command): false|string
            {
                return $this->resp;
            }
        };

        $info = $ase->query(new ServerAddress('127.0.0.1', 7797));

        $this->assertTrue($info->online);
        $this->assertSame('GameName', $info->gamename);
        $this->assertSame('1.0', $info->gameversion);
        $this->assertSame('ServerName', $info->servertitle);
        $this->assertSame('MapName', $info->mapname);
        $this->assertSame('GameType', $info->gametype);
        $this->assertSame(1, $info->numplayers);
        $this->assertSame(16, $info->maxplayers);
        $this->assertArrayHasKey('custom_key', $info->rules);
        $this->assertCount(1, $info->players);
        $this->assertSame('PlayerOne', $info->players[0]['name']);
        $this->assertSame('42', $info->players[0]['score']);
    }
}
