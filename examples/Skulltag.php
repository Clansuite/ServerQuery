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

// Skulltag server example - replace with actual server details
$serverAddress = '127.0.0.1'; // Replace with actual server IP - Skulltag servers are rare
$queryPort     = 10666; // Default Skulltag query port

print "Querying Skulltag server at {$serverAddress}:{$queryPort}..." . \PHP_EOL;

try {
    $server = $csQuery->createInstance('skulltag', $serverAddress, $queryPort);

    if ($server->query_server()) {
        print 'Server is online!' . \PHP_EOL;
        print 'Server Name: ' . $server->servertitle . \PHP_EOL;
        print 'Game: ' . $server->gamename . \PHP_EOL;
        print 'Map: ' . $server->mapname . \PHP_EOL;
        print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . \PHP_EOL;
        print 'Password Protected: ' . ((bool) $server->password ? 'Yes' : 'No') . \PHP_EOL;

        if ($server->players !== []) {
            print \PHP_EOL . 'Players:' . \PHP_EOL;

            foreach ($server->players as $player) {
                $name  = \is_scalar($player['name'] ?? 'Unknown') ? (string) ($player['name'] ?? 'Unknown') : 'Unknown';
                $score = \is_scalar($player['score'] ?? 0) ? (int) ($player['score'] ?? 0) : 0;
                $ping  = \is_scalar($player['ping'] ?? 0) ? (int) ($player['ping'] ?? 0) : 0;
                \printf("- %s (Score: %d, Ping: %d)\n", $name, $score, $ping);
            }
        }
    } else {
        print 'Server is offline or unreachable.' . \PHP_EOL;
        print 'Error: ' . $server->errstr . \PHP_EOL;
    }
} catch (Exception $e) {
    print 'Error: ' . $e->getMessage() . \PHP_EOL;
}
