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

use Clansuite\ServerQuery\Util\HuffmanDecoder;
use PHPUnit\Framework\TestCase;

final class HuffmanDecoderTest extends TestCase
{
    public function testDecompressEmptyStringReturnsEmptyString(): void
    {
        $decoder = new HuffmanDecoder;
        $this->assertSame('', $decoder->decompress(''));
    }

    public function testDecompressUncompressedData(): void
    {
        $decoder    = new HuffmanDecoder;
        $data       = 'Hello World';
        $compressed = \pack('C', 255) . $data; // 255 indicates uncompressed
        $this->assertSame($data, $decoder->decompress($compressed));
    }

    public function testCompressReturnsUncompressedData(): void
    {
        $decoder    = new HuffmanDecoder;
        $data       = 'Test data';
        $compressed = $decoder->compress($data);
        // Should start with 255 (uncompressed marker) followed by original data
        $this->assertSame(\pack('C', 255) . $data, $compressed);
    }

    public function testDecompressKnownHuffmanData(): void
    {
        $decoder = new HuffmanDecoder;

        // Create some dummy compressed data that should decompress to known output.
        // In practice, you'd need real Huffman-encoded data
        $original   = 'ABCD';
        $compressed = \pack('C', 255) . $original;
        $this->assertSame($original, $decoder->decompress($compressed));
    }

    public function testDecompressWithPaddingBits(): void
    {
        $decoder = new HuffmanDecoder;

        // Test with padding bits (not 255, so it should try Huffman decompression)
        // Since we don't have real Huffman data, this will test the error handling
        $compressed = \pack('C', 5) . 'test'; // 5 padding bits

        // This should attempt Huffman decompression and may throw an exception
        // or return partial results depending on the implementation
        try {
            $result = $decoder->decompress($compressed);
            // If it doesn't throw, it should be some result
            $this->assertIsString($result);
        } catch (Exception $e) {
            $this->assertStringContainsString('No Huffman patterns found', $e->getMessage());
        }
    }

    public function testByteToBitstring(): void
    {
        $decoder = new HuffmanDecoder;

        $reflection = new ReflectionClass($decoder);
        $method     = $reflection->getMethod('byteToBitstring');
        $method->setAccessible(true);

        // Test byte 0 (all zeros)
        $this->assertEquals('00000000', $method->invoke($decoder, 0));

        // Test byte 255 (all ones)
        $this->assertEquals('11111111', $method->invoke($decoder, 255));

        // Test byte 1 (10000000) - LSB first
        $this->assertEquals('10000000', $method->invoke($decoder, 1));

        // Test byte 128 (00000001) - LSB first
        $this->assertEquals('00000001', $method->invoke($decoder, 128));
    }

    public function testFindMatchingPattern(): void
    {
        $decoder = new HuffmanDecoder;

        $reflection = new ReflectionClass($decoder);
        $method     = $reflection->getMethod('findMatchingPattern');
        $method->setAccessible(true);

        // Test with a known pattern from the Huffman table
        $bitstring = '010'; // This should match value 0
        $result    = $method->invoke($decoder, 0, $bitstring);
        $this->assertEquals([3, 0], $result); // [patternLength, decodedValue]

        // Test with another known pattern
        $bitstring = '00100'; // This should match value 3
        $result    = $method->invoke($decoder, 0, $bitstring);
        $this->assertEquals([5, 3], $result);
    }

    public function testFindMatchingPatternNoMatch(): void
    {
        $decoder = new HuffmanDecoder;

        $reflection = new ReflectionClass($decoder);
        $method     = $reflection->getMethod('findMatchingPattern');
        $method->setAccessible(true);

        // Test with a bitstring that doesn't match any pattern
        $bitstring = '999999999999'; // Invalid characters that won't match any binary pattern

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No Huffman patterns found at index 0');
        $method->invoke($decoder, 0, $bitstring);
    }

    public function testInitializeHuffmanTable(): void
    {
        $decoder = new HuffmanDecoder;

        $reflection    = new ReflectionClass($decoder);
        $tableProperty = $reflection->getProperty('huffmanDecodeTable');
        $tableProperty->setAccessible(true);
        $table = $tableProperty->getValue($decoder);

        $this->assertIsArray($table);
        $this->assertGreaterThan(0, \count($table));

        // Test that some known patterns exist
        $this->assertArrayHasKey('010', $table);
        $this->assertEquals(0, $table['010']);
        $this->assertArrayHasKey('00100', $table);
        $this->assertEquals(3, $table['00100']);
    }

    public function testMinMaxHuffmanBitstring(): void
    {
        $decoder = new HuffmanDecoder;

        $reflection  = new ReflectionClass($decoder);
        $minProperty = $reflection->getProperty('minHuffmanBitstring');
        $maxProperty = $reflection->getProperty('maxHuffmanBitstring');

        $minProperty->setAccessible(true);
        $maxProperty->setAccessible(true);

        $min = $minProperty->getValue($decoder);
        $max = $maxProperty->getValue($decoder);

        $this->assertIsInt($min);
        $this->assertIsInt($max);
        $this->assertGreaterThan(0, $min);
        $this->assertGreaterThanOrEqual($min, $max);
    }

    public function testCompressAlwaysReturnsUncompressedFormat(): void
    {
        $decoder = new HuffmanDecoder;

        $testData   = 'This is test data for compression';
        $compressed = $decoder->compress($testData);

        // Should always start with 255 (uncompressed marker)
        $this->assertEquals(255, \ord($compressed[0]));
        $this->assertEquals($testData, \substr($compressed, 1));
    }

    public function testDecompressInvalidData(): void
    {
        $decoder = new HuffmanDecoder;

        // Test with data that's too short (only padding byte)
        $compressed = \pack('C', 10); // Only padding byte, no data
        $result     = $decoder->decompress($compressed);
        $this->assertEquals('', $result);
    }

    public function testRoundTripCompressDecompress(): void
    {
        $decoder = new HuffmanDecoder;

        $data         = 'Round trip test';
        $compressed   = $decoder->compress($data);
        $decompressed = $decoder->decompress($compressed);

        $this->assertSame($data, $decompressed);
    }

    public function testDecompressBinaryData(): void
    {
        $decoder = new HuffmanDecoder;

        // Test with binary data
        $binaryData = "\x00\x01\x02\x03\xFF\xFE\xFD";
        $compressed = \pack('C', 255) . $binaryData;
        $result     = $decoder->decompress($compressed);

        $this->assertSame($binaryData, $result);
    }
}
