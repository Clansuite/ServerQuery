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

namespace Tests\CSQuery\ServerProtocols;

require_once __DIR__ . '/../FixtureReplayHelper.php';

use PHPUnit\Framework\TestCase;
use Tests\CSQuery\FixtureReplayHelper;

final class CounterStrike16FixtureReplayTest extends TestCase
{
    use FixtureReplayHelper;

    protected function setUp(): void
    {
        // Set fixture directory and protocol (CounterStrike16 maps to CounterStrike16 class)
        $this->setUpFixtureTest(__DIR__ . '/../../../tests/fixtures', 'CounterStrike16');
    }

    public function testReplayCapturedCounterStrike16Fixture(): void
    {
        $json = __DIR__ . '/../../../tests/fixtures/counterstrike16/v1_0/capture_51_83_164_145_27015.json';
        $this->runFixtureReplayTest($json);
    }
}
