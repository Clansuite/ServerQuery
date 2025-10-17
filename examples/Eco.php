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

// Eco server example - replace with actual server details
$serverAddress = '46.4.156.247';
$queryPort     = 3001; // Web port, usually game port +1

print "Querying Eco server at {$serverAddress}:{$queryPort}..." . \PHP_EOL;

$csQuery = new CSQuery;

try {
    $server = $csQuery->createInstance('eco', $serverAddress, $queryPort);

    if ($server->query_server()) {
        print 'Server is online!' . \PHP_EOL;
        print 'Server Name: ' . $server->servertitle . \PHP_EOL;
        print 'Map: ' . $server->mapname . \PHP_EOL;
        print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . \PHP_EOL;

        if ($server->players !== []) {
            print 'Players list (' . \count($server->players) . "):\n";

            foreach (\array_slice($server->players, 0, 5) as $p) {  // show first 5
                $name  = \is_scalar($p['name'] ?? 'Unknown') ? (string) ($p['name'] ?? 'Unknown') : 'Unknown';
                $score = \is_scalar($p['score'] ?? 0) ? (int) ($p['score'] ?? 0) : 0;
                \printf("  - %s (Score: %d)\n", $name, $score);
            }
        } else {
            print '  No players online.' . \PHP_EOL;
        }

        if ($server->rules !== []) {
            print 'Server Rules/Variables:' . \PHP_EOL;

            foreach ($server->rules as $key => $value) {
                $val = \is_scalar($value) ? (string) $value : '';
                \printf('  %s: %s%s', $key, $val, \PHP_EOL);
            }
        }
    } else {
        print 'Server is offline.' . \PHP_EOL;
    }
} catch (Exception $e) {
    print 'Error: ' . $e->getMessage() . \PHP_EOL;
}
