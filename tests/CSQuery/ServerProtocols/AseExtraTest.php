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

final class AseExtraTest extends TestCase
{
    public function testQueryServerNoReplySetsErrstr(): void
    {
        $ase = new class('127.0.0.1', 7777) extends Ase
        {
            public function __construct(?string $a = null, ?int $p = null)
            {
                parent::__construct($a, $p);
            }

            protected function sendCommand(string $address, int $port, string $command): false|string
            {
                return '';
            }
        };

        $this->assertFalse($ase->query_server());
        $rp = new ReflectionProperty(Ase::class, 'errstr');
        $rp->setAccessible(true);
        $this->assertSame('No reply received', $rp->getValue($ase));
    }

    public function testQueryServerResponseTooShort(): void
    {
        $ase = new class('127.0.0.1', 7777) extends Ase
        {
            public function __construct(?string $a = null, ?int $p = null)
            {
                parent::__construct($a, $p);
            }

            protected function sendCommand(string $address, int $port, string $command): false|string
            {
                return 'EYE';
            }
        };

        $this->assertFalse($ase->query_server());
        $rp = new ReflectionProperty(Ase::class, 'errstr');
        $rp->setAccessible(true);
        $this->assertSame('Response too short', $rp->getValue($ase));
    }

    public function testQueryServerInvalidHeader(): void
    {
        $ase = new class('127.0.0.1', 7777) extends Ase
        {
            public function __construct(?string $a = null, ?int $p = null)
            {
                parent::__construct($a, $p);
            }

            protected function sendCommand(string $address, int $port, string $command): false|string
            {
                return 'BAD1' . "\x00\x00\x00\x00";
            }
        };

        $this->assertFalse($ase->query_server());
        $rp = new ReflectionProperty(Ase::class, 'errstr');
        $rp->setAccessible(true);
        $this->assertSame('Invalid header', $rp->getValue($ase));
    }

    public function testQueryServerParsesAllPlayerFlagsAndMultiplePlayers(): void
    {
        // Build payload
        $payload = 'EYE1';

        $lp = static function (string $s)
        {
            return \chr(\strlen($s) + 1) . $s;
        };

        // Provide empty fixed header fields to hit code paths where they are skipped
        $payload .= $lp(''); // gamename
        $payload .= $lp(''); // port
        $payload .= $lp(''); // servername
        $payload .= $lp(''); // gametype
        $payload .= $lp(''); // map
        $payload .= $lp(''); // version
        $payload .= $lp(''); // password
        $payload .= $lp('0'); // num_players
        $payload .= $lp('0'); // max_players

        // Add multiple key/value pairs
        $payload .= $lp('k1') . $lp('v1');
        $payload .= $lp('k2') . $lp('v2');
        $payload .= $lp('0'); // terminator

        // Player 1: all flags set (1|2|4|8|16|32 = 63)
        $p1 = \chr(63);
        $p1 .= $lp('Name1');
        $p1 .= $lp('Team1');
        $p1 .= $lp('Skin1');
        $p1 .= $lp('10'); // score
        $p1 .= $lp('50'); // ping
        $p1 .= $lp('120.5'); // time

        // Player 2: only name and time
        $p2 = \chr(1 | 32);
        $p2 .= $lp('Name2');
        $p2 .= $lp('120.0');

        $payload .= $p1 . $p2;

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

        $info = $ase->query(new ServerAddress('127.0.0.1', 1234));

        $this->assertTrue($info->online);
        $this->assertArrayHasKey('k1', $info->rules);
        $this->assertArrayHasKey('k2', $info->rules);

        $this->assertCount(2, $info->players);

        $this->assertSame('Name1', $info->players[0]['name']);
        $this->assertSame('Team1', $info->players[0]['team']);
        $this->assertSame('Skin1', $info->players[0]['skin']);
        $this->assertSame('10', $info->players[0]['score']);
        $this->assertSame('50', $info->players[0]['ping']);
        $this->assertSame('120.5', $info->players[0]['time']);

        $this->assertSame('Name2', $info->players[1]['name']);
        $this->assertArrayNotHasKey('team', $info->players[1]);
        $this->assertSame('120.0', $info->players[1]['time']);

        // dedicated default should be set
        $this->assertSame(1, $info->rules['dedicated']);
    }
}
