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

// Assault Cube server example
$serverAddress = '158.179.216.203'; // Real Assault Cube server
$queryPort     = 28763; // Default Assault Cube game port (query uses port + 1)

print "Querying Assault Cube server at {$serverAddress}:{$queryPort}..." . \PHP_EOL;

try {
    $server = $csQuery->createInstance('AssaultCube', $serverAddress, $queryPort);

    if ($server->query_server()) {
        print 'Server is online!' . \PHP_EOL;
        print 'Server Name: ' . $server->servertitle . \PHP_EOL;
        print 'Map: ' . $server->mapname . \PHP_EOL;
        print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . \PHP_EOL;
        print 'Password Protected: ' . ((bool) $server->password ? 'Yes' : 'No') . \PHP_EOL;
        print 'Game Type: ' . $server->gametype . \PHP_EOL;

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
        print 'Server is offline or unreachable.' . \PHP_EOL;
        print 'Error: ' . $server->errstr . \PHP_EOL;
    }
} catch (Exception $e) {
    print 'Error: ' . $e->getMessage() . \PHP_EOL;
}
