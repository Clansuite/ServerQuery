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

namespace Clansuite\ServerQuery\Util;

use function array_keys;
use function max;
use function min;
use function ord;
use function pack;
use function strlen;
use function substr;
use Exception;

/**
 * Huffman Decoder for Zandronum / Skulltag.
 */
final class HuffmanDecoder
{
    /**
     * @var array<int|string, int>
     */
    private array $huffmanDecodeTable;
    private int $minHuffmanBitstring;
    private int $maxHuffmanBitstring;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->initializeHuffmanTable();
    }

    /**
     * Decompress Huffman-encoded data.
     */
    public function decompress(string $buffer): string
    {
        // Buffer must be at least one byte (padding byte). If not, return empty.
        if ($buffer === '') {
            return '';
        }

        $paddingBits = ord($buffer[0] ?? "\0");
        $huffmanData = substr($buffer, 1);

        // If padding_bits is 255, data is uncompressed
        if ($paddingBits === 255) {
            return $huffmanData;
        }

        $huffmanLen = strlen($huffmanData);

        // If there is no data after padding byte, return empty
        if ($huffmanLen === 0) {
            return '';
        }

        $nBits = ($huffmanLen * 8) - $paddingBits;

        // Convert bytes to bit string
        $bitstring = '';

        for ($i = 0; $i < $huffmanLen; $i++) {
            $byte = $huffmanData[$i] ?? "\0";
            $bitstring .= $this->byteToBitstring(ord($byte));
        }

        $result = [];
        $idx    = 0;

        while ($idx < $nBits) {
            [$patternLen, $decoded] = $this->findMatchingPattern($idx, $bitstring);
            $result[]               = $decoded;
            $idx += $patternLen;
        }

        return pack('C*', ...$result);
    }

    /**
     * Compress data using Huffman encoding.
     */
    public function compress(string $data): string
    {
        // For initial implementation, return uncompressed data with padding=255
        // Full implementation would need the reverse Huffman encoding
        return pack('C', 255) . $data;
    }

    private function initializeHuffmanTable(): void
    {
        $this->huffmanDecodeTable = [
            '0000'       => 128, '00010000' => 38, '00010001' => 34, '000100100' => 80,
            '0001001010' => 110, '0001001011' => 144, '00010011' => 67, '000101000' => 74,
            '0001010010' => 243, '0001010011' => 142, '00010101' => 37, '000101100' => 124,
            '000101101'  => 58, '00010111' => 182, '00011000' => 36, '0001100100' => 221,
            '0001100101' => 131, '0001100110' => 245, '0001100111' => 163, '00011010' => 35,
            '000110110'  => 113, '000110111' => 85, '00011100' => 41, '000111010' => 77,
            '0001110110' => 199, '0001110111' => 130, '000111100' => 206, '0001111010' => 185,
            '0001111011' => 153, '000111110' => 70, '000111111' => 118, '00100' => 3,
            '00101'      => 5, '0011000' => 24, '0011001000' => 198, '0011001001' => 190,
            '001100101'  => 63, '0011001100' => 139, '0011001101' => 186, '001100111' => 75,
            '00110100'   => 44, '0011010100' => 240, '0011010101' => 218, '001101011' => 56,
            '00110110'   => 40, '00110111' => 39, '0011100000' => 244, '0011100001' => 247,
            '001110001'  => 81, '00111001' => 65, '001110100' => 9, '001110101' => 125,
            '001110110'  => 68, '001110111' => 60, '001111000' => 25, '0011110010' => 191,
            '0011110011' => 138, '001111010' => 86, '001111011' => 17, '001111100' => 23,
            '0011111010' => 220, '0011111011' => 178, '0011111100' => 165, '0011111101' => 194,
            '001111111'  => 14, '010' => 0, '011000000' => 208, '0110000010' => 150,
            '0110000011' => 157, '01100001' => 181, '01100010' => 222, '0110001100' => 216,
            '0110001101' => 230, '011000111' => 211, '0110010000' => 252, '0110010001' => 141,
            '011001001'  => 10, '01100101' => 42, '0110011000' => 134, '0110011001' => 135,
            '011001101'  => 104, '011001110' => 103, '0110011110' => 187, '0110011111' => 225,
            '01101'      => 95, '0111' => 32, '10000000' => 57, '100000010' => 61,
            '1000000110' => 183, '1000000111' => 237, '1000001000' => 233, '1000001001' => 234,
            '1000001010' => 246, '1000001011' => 203, '1000001100' => 250, '1000001101' => 147,
            '100000111'  => 79, '1000010' => 129, '100001100' => 7, '1000011010' => 143,
            '1000011011' => 136, '100001110' => 20, '1000011110' => 179, '1000011111' => 148,
            '100010000'  => 28, '100010001' => 106, '100010010' => 101, '100010011' => 87,
            '10001010'   => 66, '1000101100' => 180, '1000101101' => 219, '1000101110' => 227,
            '1000101111' => 241, '10001100' => 26, '100011010' => 251, '1000110110' => 229,
            '1000110111' => 214, '10001110' => 54, '10001111' => 69, '1001000000' => 231,
            '1001000001' => 212, '1001000010' => 156, '1001000011' => 176, '100100010' => 93,
            '100100011'  => 83, '100100100' => 96, '100100101' => 253, '100100110' => 30,
            '100100111'  => 13, '1001010000' => 175, '1001010001' => 254, '100101001' => 94,
            '100101010'  => 159, '100101011' => 27, '100101100' => 8, '1001011010' => 204,
            '1001011011' => 226, '10010111' => 78, '100110000' => 107, '100110001' => 88,
            '100110010'  => 31, '1001100110' => 137, '1001100111' => 169, '1001101000' => 215,
            '1001101001' => 145, '100110101' => 6, '10011011' => 4, '1001110' => 127,
            '100111100'  => 99, '1001111010' => 209, '1001111011' => 217, '1001111100' => 213,
            '1001111101' => 238, '1001111110' => 177, '1001111111' => 170, '1010' => 132,
            '101100000'  => 22, '101100001' => 12, '10110001' => 114, '1011001000' => 158,
            '1011001001' => 197, '101100101' => 97, '10110011' => 45, '10110100' => 46,
            '101101010'  => 112, '1011010110' => 174, '1011010111' => 249, '101101100' => 224,
            '101101101'  => 102, '1011011100' => 171, '1011011101' => 151, '101101111' => 193,
            '101110000'  => 15, '101110001' => 16, '101110010' => 2, '101110011' => 168,
            '10111010'   => 49, '101110110' => 91, '101110111' => 146, '10111100' => 48,
            '101111010'  => 173, '101111011' => 29, '101111100' => 19, '101111101' => 126,
            '101111110'  => 92, '101111111' => 242, '110000000' => 205, '110000001' => 192,
            '1100000100' => 235, '1100000101' => 149, '110000011' => 255, '110000100' => 223,
            '110000101'  => 184, '11000011' => 248, '110001000' => 108, '110001001' => 236,
            '110001010'  => 111, '110001011' => 90, '110001100' => 117, '110001101' => 115,
            '11000111'   => 71, '11001000' => 11, '11001001' => 50, '110010100' => 188,
            '110010101'  => 119, '110010110' => 122, '1100101110' => 167, '1100101111' => 162,
            '1100110'    => 160, '11001110' => 133, '110011110' => 123, '110011111' => 21,
            '11010000'   => 59, '1101000100' => 155, '1101000101' => 154, '110100011' => 98,
            '1101001'    => 43, '11010100' => 76, '11010101' => 51, '110101100' => 201,
            '110101101'  => 116, '11010111' => 72, '110110000' => 109, '110110001' => 100,
            '11011001'   => 121, '110110100' => 195, '110110101' => 232, '11011011' => 18,
            '110111'     => 1, '1110000' => 164, '111000100' => 120, '111000101' => 189,
            '11100011'   => 73, '11100100' => 196, '111001010' => 239, '111001011' => 210,
            '11100110'   => 64, '11100111' => 62, '11101' => 89, '1111000' => 33,
            '111100100'  => 228, '111100101' => 161, '11110011' => 55, '11110100' => 84,
            '11110101'   => 152, '1111011' => 47, '111110000' => 207, '111110001' => 172,
            '11111001'   => 140, '11111010' => 82, '11111011' => 166, '11111100' => 53,
            '11111101'   => 105, '11111110' => 52, '111111110' => 202, '111111111' => 200,
        ];

        // Ensure keys are strings (PHP may cast numeric-string keys to int)
        $converted = [];

        foreach ($this->huffmanDecodeTable as $k => $v) {
            $converted[(string) $k] = $v;
        }
        $this->huffmanDecodeTable = $converted;

        $lengths = [];

        foreach (array_keys($this->huffmanDecodeTable) as $k) {
            $lengths[] = strlen((string) $k);
        }

        // min()/max() on a non-empty array of ints is safe because the table is populated above
        $this->minHuffmanBitstring = (int) min($lengths);
        $this->maxHuffmanBitstring = (int) max($lengths);
    }

    /**
     * Convert byte to 8-character bit string.
     */
    private function byteToBitstring(int $byteval): string
    {
        $result = '';

        for ($i = 0; $i < 8; $i++) {
            $result .= ((($byteval & (1 << $i)) !== 0) ? '1' : '0');
        }

        return $result;
    }

    /**
     * Find matching Huffman pattern.
     *
     * Returns an array with two integers: [bitstringLength, decodedValue]
     *
     * @return array{int, int}
     */
    private function findMatchingPattern(int $idx, string $bitstring): array
    {
        for ($bitstringLength = $this->minHuffmanBitstring; $bitstringLength <= $this->maxHuffmanBitstring; $bitstringLength++) {
            $frontslice = substr($bitstring, $idx, $bitstringLength);

            if (isset($this->huffmanDecodeTable[$frontslice])) {
                return [$bitstringLength, $this->huffmanDecodeTable[$frontslice]];
            }
        }

        throw new Exception("No Huffman patterns found at index {$idx}");
    }
}
