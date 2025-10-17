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

use Clansuite\Capture\Extractor\VersionNormalizer;
use PHPUnit\Framework\TestCase;

final class VersionNormalizerTest extends TestCase
{
    public function testNormalizeSimple(): void
    {
        $n = new VersionNormalizer;
        $this->assertSame('v1_2_3', $n->normalize('1.2.3'));
        $this->assertSame('v1_2_3_alpha', $n->normalize('1.2.3-alpha'));
        $this->assertSame('v1_2_3', $n->normalize('1.2.3.')); // trailing dot
    }
}
