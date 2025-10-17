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

namespace Clansuite\Capture\Extractor;

use function preg_replace;
use function trim;

/**
 * Normalizes version strings by replacing special characters with underscores for consistent formatting.
 */
class VersionNormalizer
{
    /**
     * normalize method.
     */
    public function normalize(string $version): string
    {
        // Replace dots and other special chars with underscores
        $normalized = preg_replace('/[^a-zA-Z0-9]/', '_', $version);

        // Remove multiple consecutive underscores
        $normalized = preg_replace('/_+/', '_', (string) $normalized);

        // Remove leading/trailing underscores
        $normalized = trim((string) $normalized, '_');

        return 'v' . $normalized;
    }
}
