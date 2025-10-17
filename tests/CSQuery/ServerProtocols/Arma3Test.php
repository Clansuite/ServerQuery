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

use Clansuite\ServerQuery\ServerProtocols\Arma3;
use PHPUnit\Framework\TestCase;

final class Arma3Test extends TestCase
{
    public function testParseArma3BinaryDataParsesAllSections(): void
    {
        $arma3 = new Arma3;

        // Build binary data according to parse_arma3_binary_data expectations
        $dlc_byte1 = \chr(0x01); // low byte - will set first DLC flag
        $dlc_byte2 = \chr(0x00);

        // Difficulty: set bits so 3rd_person=1, advanced_flight_mode=1, difficulty_ai=2, difficulty_level=1
        $difficulty = \chr((1 << 7) | (1 << 6) | (2 << 3) | 1);

        $crosshair = \chr(7);

        // One mod
        $mod_count = \chr(1);

        // Mod hash (4 bytes, big-endian)
        $hash = \pack('N', 0x12345678);

        // Info byte: mark as dlc (0x10), steam id len lower nibble set to 0
        $info_byte = \chr(0x10);

        // Steam id (4 bytes)
        $steam_id = \pack('N', 0x9ABCDEF0);

        $mod_name = 'modname';
        $name_len = \chr(\strlen($mod_name));

        // Signatures: one signature 'abc'
        $signature_count = \chr(1);
        $sig             = 'abc';
        $sig_len         = \chr(\strlen($sig));

        $data = $dlc_byte1 . $dlc_byte2 . $difficulty . $crosshair . $mod_count
            . $hash . $info_byte . $steam_id . $name_len . $mod_name
            . $signature_count . $sig_len . $sig;

        // Call private method parse_arma3_binary_data via reflection
        $rm = new ReflectionMethod(Arma3::class, 'parse_arma3_binary_data');
        $rm->setAccessible(true);
        $rm->invoke($arma3, $data);

        $rules = (array) (new ReflectionProperty(Arma3::class, 'rules'));

        // Access the instance property
        $rp = new ReflectionProperty(Arma3::class, 'rules');
        $rp->setAccessible(true);
        $parsedRules = $rp->getValue($arma3);

        $this->assertSame(1, $parsedRules['3rd_person']);
        $this->assertSame(1, $parsedRules['advanced_flight_mode']);
        $this->assertSame(2, $parsedRules['difficulty_ai']);
        $this->assertSame(1, $parsedRules['difficulty_level']);
        $this->assertSame(7, $parsedRules['crosshair']);

        $this->assertIsArray($parsedRules['dlcs']);
        $this->assertContains('Karts', $parsedRules['dlcs']);

        $this->assertSame(1, $parsedRules['mod_count']);
        $this->assertIsArray($parsedRules['mods']);
        $this->assertCount(1, $parsedRules['mods']);

        $mod = $parsedRules['mods'][0];
        $this->assertSame(\dechex(0x12345678), $mod['hash']);
        $this->assertTrue($mod['dlc']);
        $this->assertSame(0x9ABCDEF0, $mod['steam_id']);
        $this->assertSame('modname', $mod['name']);

        $this->assertSame(1, $parsedRules['signature_count']);
        $this->assertSame(['abc'], $parsedRules['signatures']);
    }

    public function testParseArma3BinaryDataTooShortDoesNothing(): void
    {
        $arma3 = new Arma3;

        $rm = new ReflectionMethod(Arma3::class, 'parse_arma3_binary_data');
        $rm->setAccessible(true);

        // Short data (<8 bytes) should just return without error and not populate rules
        $rm->invoke($arma3, "\x00\x01\x02");

        $rp = new ReflectionProperty(Arma3::class, 'rules');
        $rp->setAccessible(true);
        $parsedRules = $rp->getValue($arma3);

        $this->assertEmpty($parsedRules);
    }
}
