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

namespace Tests\CSQuery;

use Clansuite\ServerQuery\CSQuery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

class CSQueryPrivateSortTest extends TestCase
{
    public function testSortByScoreBranches(): void
    {
        $a = ['score' => 10];
        $b = ['score' => 10];
        $this->assertSame(0, $this->invokePrivate('sortByScore', [$a, $b]));

        $a = ['score' => 5];
        $b = ['score' => 10];
        $this->assertSame(1, $this->invokePrivate('sortByScore', [$a, $b]));

        $a = ['score' => 15];
        $b = ['score' => 10];
        $this->assertSame(-1, $this->invokePrivate('sortByScore', [$a, $b]));
    }

    public function testSortByFragsBranches(): void
    {
        $a = ['frags' => 0];
        $b = ['frags' => 0];
        $this->assertSame(0, $this->invokePrivate('sortByFrags', [$a, $b]));

        $a = ['frags' => 2];
        $b = ['frags' => 5];
        $this->assertSame(1, $this->invokePrivate('sortByFrags', [$a, $b]));

        $a = ['frags' => 7];
        $b = ['frags' => 3];
        $this->assertSame(-1, $this->invokePrivate('sortByFrags', [$a, $b]));
    }

    public function testSortByDeathsBranches(): void
    {
        $a = ['deaths' => 1];
        $b = ['deaths' => 1];
        $this->assertSame(0, $this->invokePrivate('sortByDeaths', [$a, $b]));

        $a = ['deaths' => 0];
        $b = ['deaths' => 2];
        $this->assertSame(1, $this->invokePrivate('sortByDeaths', [$a, $b]));

        $a = ['deaths' => 5];
        $b = ['deaths' => 2];
        $this->assertSame(-1, $this->invokePrivate('sortByDeaths', [$a, $b]));
    }

    public function testSortByTimeBranches(): void
    {
        $a = ['time' => 1.0];
        $b = ['time' => 1.0];
        $this->assertSame(0, $this->invokePrivate('sortByTime', [$a, $b]));

        $a = ['time' => 0.5];
        $b = ['time' => 2.0];
        $this->assertSame(1, $this->invokePrivate('sortByTime', [$a, $b]));

        $a = ['time' => 3.0];
        $b = ['time' => 1.0];
        $this->assertSame(-1, $this->invokePrivate('sortByTime', [$a, $b]));
    }

    public function testSortByKillsBranches(): void
    {
        $a = ['kills' => 4];
        $b = ['kills' => 4];
        $this->assertSame(0, $this->invokePrivate('sortByKills', [$a, $b]));

        $a = ['kills' => 1];
        $b = ['kills' => 3];
        $this->assertSame(1, $this->invokePrivate('sortByKills', [$a, $b]));

        $a = ['kills' => 9];
        $b = ['kills' => 2];
        $this->assertSame(-1, $this->invokePrivate('sortByKills', [$a, $b]));
    }

    public function testUnserializeInvalidBase64Throws(): void
    {
        $this->expectException(RuntimeException::class);
        $factory = new CSQuery;

        // Craft invalid base64 substring
        $factory->unserialize('CSQuery:!notbase64!');
    }

    public function testGetProtocolsMapReturnsArray(): void
    {
        $factory = new CSQuery;
        $map     = $factory->getProtocolsMap();

        $this->assertIsArray($map);
        $this->assertNotEmpty($map);
    }

    private function invokePrivate(string $method, array $args)
    {
        $rc = new ReflectionClass(CSQuery::class);
        $m  = $rc->getMethod($method);
        $m->setAccessible(true);

        $instance = new CSQuery;

        return $m->invokeArgs($instance, $args);
    }
}
