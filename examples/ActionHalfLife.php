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

use Clansuite\ServerQuery\CSQuery;

$config = [
    'server_addr'     => '127.0.0.1', // Replace with actual Action Half-Life server IP
    'server_port'     => 27015, // Default query port
    'request_timeout' => 2,
];

$factory = new CSQuery;
$server  = $factory->createInstance('ahl', $config['server_addr'], $config['server_port']);

print "Querying Action Half-Life server {$config['server_addr']}:{$config['server_port']}...\n";

if ($server->query_server(true, true)) {  // enable players and rules
    print 'Server: ' . $server->servertitle . "\n";
    print 'Map: ' . $server->mapname . "\n";
    print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . "\n";

    if ($server->players !== []) {
        print 'Players list (' . \count($server->players) . "):\n";

        foreach (\array_slice($server->players, 0, 5) as $p) {  // show first 5
            $name  = $p['name'] ?? '(unknown)';
            $score = $p['score'] ?? 0;
            $time  = $p['time'] ?? '0';
            $name  = \is_scalar($name) ? (string) $name : '(unknown)';
            $score = \is_scalar($score) ? (string) $score : '0';
            $time  = \is_scalar($time) ? (string) $time : '0';
            print ' - ' . $name . ' (score: ' . $score . ', time: ' . $time . ")\n";
        }

        if (\count($server->players) > 5) {
            print ' ... and ' . (\count($server->players) - 5) . " more\n";
        }
    } else {
        print "Player list not available.\n";
    }

    if ($server->rules !== []) {
        print 'Rules count: ' . \count($server->rules) . "\n";
    }
} else {
    print 'Failed to query server: ' . $server->errstr . "\n";
}
