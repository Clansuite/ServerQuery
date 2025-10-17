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

use Clansuite\ServerQuery\ServerProtocols\ArmaReforger;
use PHPUnit\Framework\TestCase;

final class ArmaReforgerTest extends TestCase
{
    public function testConstructorAndMetadata(): void
    {
        $a = new ArmaReforger('127.0.0.1', 2302);

        $this->assertSame('ARMA: Reforger', $a->name);
        $this->assertContains('ARMA: Reforger', $a->supportedGames);
        $this->assertSame('A2S', $a->protocol);

        // port_diff is protected; reflect to check its value
        $rp = new ReflectionProperty(ArmaReforger::class, 'port_diff');
        $rp->setAccessible(true);
        $this->assertSame(1, $rp->getValue($a));
    }
}
