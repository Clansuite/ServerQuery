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

use Clansuite\ServerQuery\ServerProtocols\Quake3Arena;

$config = [
    'server_addr'     => '68.232.163.58',
    'server_port'     => 12203, // query port
    'request_timeout' => 2,
];

$server = new Quake3Arena($config['server_addr'], $config['server_port']);

if ($server->query_server()) {
    print "✅ Server is online!\n\n";
    print "\nℹ️  Server Information:\n";
    print 'Server Title: ' . $server->servertitle . \PHP_EOL;
    print 'Map: ' . $server->mapname . \PHP_EOL;
    print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . \PHP_EOL;
    print 'Game Version: ' . $server->gameversion . \PHP_EOL;

    if ($server->players !== []) {
        print 'Players list (' . \count($server->players) . "):\n";

        foreach (\array_slice($server->players, 0, 5) as $p) {  // show first 5
            $name  = \is_scalar($p['name'] ?? '(unknown)') ? (string) ($p['name'] ?? '(unknown)') : '(unknown)';
            $score = \is_scalar($p['score'] ?? 0) ? (int) ($p['score'] ?? 0) : 0;
            $time  = \is_scalar($p['time'] ?? '0') ? (string) ($p['time'] ?? '0') : '0';
            \printf(" - %s (score: %d, time: %s)\n", $name, $score, $time);
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
    print '❌ Failed: ' . $server->errstr . \PHP_EOL;
    \print_r($server->debug);
}
