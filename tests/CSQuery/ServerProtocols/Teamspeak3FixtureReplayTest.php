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

use Clansuite\ServerQuery\ServerProtocols\Teamspeak3;
use PHPUnit\Framework\TestCase;

final class Teamspeak3FixtureReplayTest extends TestCase
{
    public function testParseFixturePropertiesAndClients(): void
    {
        $fixture = \file_get_contents(__DIR__ . '/../../fixtures/teamspeak3/capture_127_0_0_1_10011.txt');
        $this->assertNotFalse($fixture, 'fixture must be readable');

        // split into lines as our implementation would
        $lines = \explode("\n", \trim($fixture));

        // details are on line 3 and clients on line 5 in our fixture
        $details = $lines[2] ?? '';
        $clients = $lines[4] ?? '';

        $proto = new Teamspeak3('127.0.0.1', 10011);

        $refClass   = new ReflectionClass($proto);
        $parseProps = $refClass->getMethod('parseProperties');

        $props = $parseProps->invoke($proto, $details);

        $this->assertArrayHasKey('virtualserver_name', $props);
        $this->assertEquals('TestServer', $props['virtualserver_name']);
        $this->assertEquals('3.13.6', $props['virtualserver_version']);

        $parseClients = $refClass->getMethod('parseClientList');

        $parsedClients = $parseClients->invoke($proto, $clients);

        $this->assertCount(2, $parsedClients);
        $this->assertEquals('Alice', $parsedClients[0]['client_nickname']);
        $this->assertEquals('Bob', $parsedClients[1]['client_nickname']);
    }
}
