<?php
declare(strict_types=1);

/**
 * Clansuite Server Query
 *
 * SPDX-FileCopyrightText: 2003-2025 Jens A. Koch
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

require __DIR__ . '/../vendor/autoload.php';

use Clansuite\ServerQuery\ServerProtocols\Teamspeak3;

$address = 'ts3.kskclan.com'; // visible at https://kskclan.com/teamspeak_viewer
$port    = 10011; // ServerQuery port (default 10011). Use 9987 for client connections.

print "Querying Teamspeak3 server {$address}:{$port}...\n";

$proto = new Teamspeak3($address, $port);

if ($proto->query_server(true, true)) {
    print 'Server: ' . $proto->servertitle . "\n";
    print 'Players: ' . $proto->numplayers . '/' . $proto->maxplayers . "\n";

    if ($proto->players !== []) {
        print "--- PLAYERS ---\n";

        foreach ($proto->players as $p) {
            $name = \is_scalar($p['name'] ?? '') ? (string) ($p['name'] ?? '') : '';
            \printf(" - %s\n", $name);
        }
    }
} else {
    print 'Server is offline or not reachable. Err: ' . $proto->errstr . "\n";
}

// Print debug buffer if present to aid diagnostics
if ($proto->debug !== []) {
    print "--- DEBUG ---\n";

    foreach ($proto->debug as $d) {
        $debug = \is_scalar($d) ? (string) $d : '';
        print $debug . "\n";
    }
}
