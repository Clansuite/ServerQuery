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

use Clansuite\ServerQuery\ServerProtocols\Dayz;

$config = [
    'server_addr'     => '94.156.155.60',
    'server_port'     => 31073, // query port
    'request_timeout' => 2,
];

$server = new Dayz($config['server_addr'], $config['server_port']);

print "Querying DayZ server {$config['server_addr']}:{$config['server_port']}...\n";

if ($server->query_server(true, true)) {  // enable players and rules
    print 'Server: ' . $server->servertitle . "\n";
    print 'Map: ' . $server->mapname . "\n";
    print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . "\n";

    if ($server->players !== []) {
        print 'Players list (' . \count($server->players) . "):\n";

        foreach (\array_slice($server->players, 0, 5) as $p) {  // show first 5
            $name  = \is_scalar($p['name'] ?? 'Unknown') ? (string) ($p['name'] ?? 'Unknown') : 'Unknown';
            $score = \is_scalar($p['score'] ?? 0) ? (int) ($p['score'] ?? 0) : 0;
            \printf("  - %s (Score: %d)\n", $name, $score);
        }
    } else {
        print "No players list available\n";
    }
} else {
    print "Failed to query server\n";
}
