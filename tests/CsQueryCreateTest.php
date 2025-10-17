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

use Clansuite\ServerQuery\CSQuery;
use Clansuite\ServerQuery\ServerProtocols\Quake3Arena;
use PHPUnit\Framework\TestCase;

final class CsQueryCreateTest extends TestCase
{
    public function testCreateInstanceReturnsProtocolObject(): void
    {
        // Do not call query_server() to avoid network access.
        // createInstance is implemented as an instance method in this codebase
        // so create a small factory object and call it.
        $factory  = new CSQuery;
        $instance = $factory->createInstance('Quake3a', '127.0.0.1', 27960);

        $this->assertIsObject($instance, 'createInstance should return an object');
        $this->assertInstanceOf(Quake3Arena::class, $instance, 'Expected instance of class Quake3Arena');
    }
}
