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

require __DIR__ . '/../vendor/autoload.php';

use Clansuite\ServerQuery\CSQuery;

// Palworld server example - replace with actual server details
$serverAddress = '127.0.0.1'; // Replace with actual server IP
$queryPort     = 8212; // Default Palworld REST API port

print "Querying Palworld server at {$serverAddress}:{$queryPort}..." . \PHP_EOL;

$csQuery = new CSQuery;

try {
    $server = $csQuery->createInstance('palworld', $serverAddress, $queryPort);

    if ($server->query_server()) {
        print 'Server is online!' . \PHP_EOL;
        print 'Server Name: ' . $server->servertitle . \PHP_EOL;
        print 'Game: ' . $server->gamename . \PHP_EOL;
        print 'Version: ' . $server->gameversion . \PHP_EOL;
        print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . \PHP_EOL;
        print 'Password Protected: ' . ((bool) $server->password ? 'Yes' : 'No') . \PHP_EOL;

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
        print 'Server is offline.' . \PHP_EOL;
    }
} catch (Exception $e) {
    print 'Error: ' . $e->getMessage() . \PHP_EOL;
}
