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

use PHPUnit\Framework\TestCase;

final class RavagedFixtureTest extends TestCase
{
    public function testFixtureExistsAndHasMetadata(): never
    {
        // placeholder

        // Note: No active servers found, so fixture will not exist
        $this->markTestSkipped('No active Ravaged servers found - fixture cannot be created');
    }
}
