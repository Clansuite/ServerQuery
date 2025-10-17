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

use const PHP_INT_MAX;
use function random_int;

/**
 * Helper utilities for implementing a lightweight Steam Query proxy in PHP.
 */
final class SteamProxyHelper
{
    /**
     * @var array<int>
     */
    private array $challenges = [];
    private int $index        = 0;
    private readonly int $size;

    public static function jenkinsHash(int $value): int
    {
        // emulate the 32-bit Jenkins mix used in the C implementation
        $value = ($value + 0x7ED55D16) + (($value << 12) & 0xFFFFFFFF);
        $value = ($value ^ 0xC761C23C) ^ (($value >> 19) & 0xFFFFFFFF);
        $value = ($value + 0x165667B1) + (($value << 5) & 0xFFFFFFFF);
        $value = ($value + 0xD3A2646C) ^ (($value << 9) & 0xFFFFFFFF);
        $value = ($value + 0xFD7046C5) + (($value << 3) & 0xFFFFFFFF);
        $value = ($value ^ 0xB55A4F09) ^ (($value >> 16) & 0xFFFFFFFF);

        return $value & 0xFFFFFFFF;
    }

    /**
     * Constructor.
     */
    public function __construct(int $size = 6)
    {
        $this->size = $size > 0 ? $size : 6;

        // seed challenges
        for ($i = 0; $i < $this->size; $i++) {
            $this->challengeNew();
        }
    }

    /**
     * challengeNew method.
     */
    public function challengeNew(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $next = ($this->index + $i) % $this->size;
            $new  = self::jenkinsHash(random_int(1, PHP_INT_MAX));

            if ($new === 0) {
                continue;
            }

            if ($new === 0xFFFFFFFF) {
                continue;
            }
            $this->challenges[$next] = $new & 0xFFFFFFFF;
            $this->index             = $next;

            return;
        }
    }

    /**
     * challengeGet method.
     */
    public function challengeGet(int $mutate): int
    {
        if (!isset($this->challenges[$this->index])) {
            // If no challenge is set at the current index, generate a new one and use it
            $this->challengeNew();
        }

        $challenge = $this->challenges[$this->index] ?? 0;

        return ($challenge + self::jenkinsHash($mutate)) & 0xFFFFFFFF;
    }

    /**
     * challengeValidate method.
     */
    public function challengeValidate(int $challenge, int $mutate): bool
    {
        if ($challenge === 0 || $challenge === 0xFFFFFFFF) {
            return false;
        }

        $idx = $this->index;

        for ($i = 0; $i < $this->size; $i++) {
            if (!isset($this->challenges[$idx])) {
                return false;
            }
            $chVal = $this->challenges[$idx];
            $check = ($chVal + self::jenkinsHash($mutate)) & 0xFFFFFFFF;

            if ($check === ($challenge & 0xFFFFFFFF)) {
                return true;
            }
            $idx++;

            if ($idx === $this->size) {
                $idx = 0;
            }
        }

        return false;
    }
}
