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

use Clansuite\ServerQuery\Util\PacketReader;
use PHPUnit\Framework\TestCase;

final class PacketReaderTest extends TestCase
{
    public function testConstructAndPos(): void
    {
        $buffer = 'test';
        $reader = new PacketReader($buffer);
        $this->assertSame(0, $reader->pos());
        $this->assertSame(4, $reader->remaining());
    }

    public function testReadUint8(): void
    {
        $buffer = \pack('C', 255);
        $reader = new PacketReader($buffer);
        $this->assertSame(255, $reader->readUint8());
        $this->assertSame(1, $reader->pos());
        $this->assertSame(0, $reader->remaining());
    }

    public function testReadUint8InsufficientData(): void
    {
        $buffer = '';
        $reader = new PacketReader($buffer);
        $this->assertNull($reader->readUint8());
    }

    public function testReadUint16(): void
    {
        $buffer = \pack('v', 65534);
        $reader = new PacketReader($buffer);
        $this->assertSame(65534, $reader->readUint16());
        $this->assertSame(2, $reader->pos());
    }

    public function testReadUint16InsufficientData(): void
    {
        $buffer = \pack('C', 1);
        $reader = new PacketReader($buffer);
        $this->assertNull($reader->readUint16());
    }

    public function testReadUint32(): void
    {
        $buffer = \pack('V', 4294967294);
        $reader = new PacketReader($buffer);
        $this->assertSame(4294967294, $reader->readUint32());
        $this->assertSame(4, $reader->pos());
    }

    public function testReadUint32InsufficientData(): void
    {
        $buffer = \pack('C', 1);
        $reader = new PacketReader($buffer);
        $this->assertNull($reader->readUint32());
    }

    public function testReadInt32(): void
    {
        $buffer = \pack('l', -123456);
        $reader = new PacketReader($buffer);
        $this->assertSame(-123456, $reader->readInt32());
        $this->assertSame(4, $reader->pos());
    }

    public function testReadInt32InsufficientData(): void
    {
        $buffer = \pack('C', 1);
        $reader = new PacketReader($buffer);
        $this->assertNull($reader->readInt32());
    }

    public function testReadFloat(): void
    {
        $buffer = \pack('f', 3.14159);
        $reader = new PacketReader($buffer);
        $value  = $reader->readFloat();
        $this->assertIsFloat($value);
        $this->assertEqualsWithDelta(3.14159, $value, 0.00001);
        $this->assertSame(4, $reader->pos());
    }

    public function testReadFloatInsufficientData(): void
    {
        $buffer = \pack('C', 1);
        $reader = new PacketReader($buffer);
        $this->assertNull($reader->readFloat());
    }

    public function testReadString(): void
    {
        $buffer = 'Hello' . "\x00" . 'World';
        $reader = new PacketReader($buffer);
        $this->assertSame('Hello', $reader->readString());
        $this->assertSame(6, $reader->pos());
    }

    public function testReadStringNoNullTerminator(): void
    {
        $buffer = 'Hello';
        $reader = new PacketReader($buffer);
        $this->assertNull($reader->readString());
    }

    public function testReadStringEmpty(): void
    {
        $buffer = "\x00";
        $reader = new PacketReader($buffer);
        $this->assertSame('', $reader->readString());
        $this->assertSame(1, $reader->pos());
    }

    public function testRest(): void
    {
        $buffer = 'HelloWorld';
        $reader = new PacketReader($buffer);
        $reader->readUint8(); // advance 1
        $this->assertSame('elloWorld', $reader->rest());
        $this->assertSame(10, $reader->pos());
        $this->assertSame(0, $reader->remaining());
    }

    public function testSequentialReads(): void
    {
        $buffer = \pack('C', 1) . \pack('v', 2) . \pack('V', 3) . 'Test' . "\x00";
        $reader = new PacketReader($buffer);

        $this->assertSame(1, $reader->readUint8());
        $this->assertSame(2, $reader->readUint16());
        $this->assertSame(3, $reader->readUint32());
        $this->assertSame('Test', $reader->readString());
        $this->assertSame(0, $reader->remaining());
    }
}
