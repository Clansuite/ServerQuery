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

use function ord;
use function strlen;
use function substr;
use function unpack;

/**
 * Utility class for reading binary data from network packets, providing methods to extract various data types.
 */
final class PacketReader
{
    private int $pos = 0;

    /**
     * Constructor.
     */
    public function __construct(private string $buffer)
    {
        $this->pos = 0;
    }

    /**
     * remaining method.
     */
    public function remaining(): int
    {
        return strlen($this->buffer) - $this->pos;
    }

    /**
     * pos method.
     */
    public function pos(): int
    {
        return $this->pos;
    }

    /**
     * readInt32 method.
     */
    public function readInt32(): ?int
    {
        if ($this->remaining() < 4) {
            return null;
        }
        $data = unpack('l', substr($this->buffer, $this->pos, 4));

        if ($data === false || !isset($data[1])) {
            return null;
        }

        $v = (int) $data[1];
        $this->pos += 4;

        return $v;
    }

    /**
     * readUint32 method.
     */
    public function readUint32(): ?int
    {
        if ($this->remaining() < 4) {
            return null;
        }
        $data = unpack('V', substr($this->buffer, $this->pos, 4));

        if ($data === false || !isset($data[1])) {
            return null;
        }

        $v = (int) $data[1];
        $this->pos += 4;

        return $v;
    }

    /**
     * readUint16 method.
     */
    public function readUint16(): ?int
    {
        if ($this->remaining() < 2) {
            return null;
        }
        $data = unpack('v', substr($this->buffer, $this->pos, 2));

        if ($data === false || !isset($data[1])) {
            return null;
        }

        $v = (int) $data[1];
        $this->pos += 2;

        return $v;
    }

    /**
     * readUint8 method.
     */
    public function readUint8(): ?int
    {
        if ($this->remaining() < 1) {
            return null;
        }
        $v = ord($this->buffer[$this->pos]);
        $this->pos++;

        return $v;
    }

    /**
     * readFloat method.
     */
    public function readFloat(): ?float
    {
        if ($this->remaining() < 4) {
            return null;
        }
        $data = unpack('f', substr($this->buffer, $this->pos, 4));

        if ($data === false || !isset($data[1])) {
            return null;
        }

        $v = (float) $data[1];
        $this->pos += 4;

        return $v;
    }

    /**
     * readString method.
     */
    public function readString(): ?string
    {
        $start = $this->pos;

        while ($this->pos < strlen($this->buffer) && $this->buffer[$this->pos] !== "\x00") {
            $this->pos++;
        }

        if ($this->pos >= strlen($this->buffer)) {
            return null;
        }
        $s = substr($this->buffer, $start, $this->pos - $start);
        $this->pos++; // skip null

        return $s;
    }

    /**
     * rest method.
     */
    public function rest(): string
    {
        $r         = substr($this->buffer, $this->pos);
        $this->pos = strlen($this->buffer);

        return $r;
    }
}
