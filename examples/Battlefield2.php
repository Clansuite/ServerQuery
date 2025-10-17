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

require_once __DIR__ . '/../vendor/autoload.php';

use Clansuite\ServerQuery\ServerProtocols\Bf2;

$config = [
    'server_addr'     => '144.22.214.56',
    'server_port'     => 16567, // client port
    'request_timeout' => 2,
];

$server = new Bf2($config['server_addr'], $config['server_port']);

print "Querying Battlefield 2 server {$config['server_addr']}:{$config['server_port']}...\n";

if ($server->query_server(true, true)) {  // enable players and rules
    print 'Server: ' . $server->servertitle . "\n";
    print 'Map: ' . $server->mapname . "\n";
    print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . "\n";

    if ($server->players !== []) {
        print 'Players list (' . \count($server->players) . "):\n";

        foreach (\array_slice($server->players, 0, 5) as $p) {  // show first 5
            $name = $p['name'] ?? 'Unknown';
            $team = $p['team'] ?? 'Unknown';
            $name = \is_scalar($name) ? (string) $name : 'Unknown';
            $team = \is_scalar($team) ? (string) $team : 'Unknown';
            print '  - ' . $name . ' (Team: ' . $team . ")\n";
        }
    } else {
        print "No players list available\n";
    }
} else {
    print "Failed to query server\n";
}
