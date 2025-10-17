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

require_once __DIR__ . '/../vendor/autoload.php';

use Clansuite\ServerQuery\CSQuery;

$csQuery = new CSQuery;

// PixARK server query - replace with actual server details
$serverAddress = '43.249.195.63';
$queryPort     = 30011; // Game port + 1

print "Querying PixARK server at {$serverAddress}:{$queryPort}..." . \PHP_EOL;

$server = $csQuery->createInstance('pixark', $serverAddress, $queryPort);

if ($server->query_server()) {
    print 'Server is online!' . \PHP_EOL;
    print 'Server Name: ' . $server->servertitle . \PHP_EOL;
    print 'Map: ' . $server->mapname . \PHP_EOL;
    print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . \PHP_EOL;
    print 'Game: ' . $server->gamename . \PHP_EOL;
    print 'Version: ' . $server->gameversion . \PHP_EOL;

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
    print 'Server is offline or unreachable.' . \PHP_EOL;
}
