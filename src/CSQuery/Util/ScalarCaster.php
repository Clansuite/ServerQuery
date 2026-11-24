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

use function is_object;
use function is_scalar;
use function method_exists;

/**
 * Converts values of unknown (mixed) type to scalar types.
 *
 * Protocol responses are decoded from binary packets and JSON. Their values
 * are therefore typed as mixed. Casting mixed directly is rejected by static
 * analysis, so these helpers perform the conversion behind an explicit,
 * type-safe boundary.
 */
final class ScalarCaster
{
    /**
     * Converts a mixed value to its string representation.
     *
     * Scalars and null convert exactly like a native (string) cast. Objects
     * with a __toString() method are converted as well. All other values
     * (arrays and objects without a string representation) become an empty
     * string.
     */
    public static function toString(mixed $value): string
    {
        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return '';
    }

    /**
     * Converts a mixed value to an integer.
     *
     * Scalars and null convert exactly like a native (int) cast. Arrays and
     * objects have no meaningful numeric representation and become 0.
     */
    public static function toInt(mixed $value): int
    {
        if (is_scalar($value) || $value === null) {
            return (int) $value;
        }

        return 0;
    }

    /**
     * Converts a mixed value to a float.
     *
     * Scalars and null convert exactly like a native (float) cast. Arrays and
     * objects have no meaningful numeric representation and become 0.0.
     */
    public static function toFloat(mixed $value): float
    {
        if (is_scalar($value) || $value === null) {
            return (float) $value;
        }

        return 0.0;
    }
}
