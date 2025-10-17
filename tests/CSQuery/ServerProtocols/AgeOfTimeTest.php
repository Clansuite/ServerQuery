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

use Clansuite\ServerQuery\ServerProtocols\AgeOfTime;
use Clansuite\ServerQuery\ServerProtocols\Tribes2;
use PHPUnit\Framework\TestCase;

class AgeOfTimeTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $ageOfTime = new AgeOfTime('127.0.0.1', 28000);
        $this->assertInstanceOf(AgeOfTime::class, $ageOfTime);
    }

    public function testExtendsTribes2(): void
    {
        $ageOfTime = new AgeOfTime('127.0.0.1', 28000);
        $this->assertInstanceOf(Tribes2::class, $ageOfTime);
    }

    public function testHasCorrectProtocol(): void
    {
        $ageOfTime = new AgeOfTime('127.0.0.1', 28000);
        $this->assertEquals('AgeOfTime', $ageOfTime->protocol);
    }

    public function testHasCorrectName(): void
    {
        $ageOfTime = new AgeOfTime('127.0.0.1', 28000);
        $this->assertEquals('Age of Time', $ageOfTime->name);
    }

    public function testGetNativeJoinURIReturnsAgeOfTimeUri(): void
    {
        $inst           = new AgeOfTime('example.com', 12345);
        $inst->hostport = 12345;

        $uri = $inst->getNativeJoinURI();

        $this->assertSame('ageoftime://example.com:12345', $uri);
    }
}
