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

// Satisfactory server example - replace with actual server details
$serverAddress = '127.0.0.1'; // Replace with actual server IP
$queryPort     = 15777; // Default Satisfactory query port

print "Querying Satisfactory server at {$serverAddress}:{$queryPort}..." . \PHP_EOL;

$csQuery = new CSQuery;

try {
    $server = $csQuery->createInstance('satisfactory', $serverAddress, $queryPort);

    if ($server->query_server()) {
        print 'Server is online!' . \PHP_EOL;
        print 'Server Name: ' . $server->servertitle . \PHP_EOL;
        print 'Game: ' . $server->gamename . \PHP_EOL;
        print 'Version: ' . $server->gameversion . \PHP_EOL;
        print 'Map: ' . $server->mapname . \PHP_EOL;
        print 'Game Mode: ' . $server->gametype . \PHP_EOL;
        print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . \PHP_EOL;

        if ($server->players !== []) {
            print 'Player List:' . \PHP_EOL;

            foreach ($server->players as $player) {
                if (\is_array($player)) {
                    $name  = $player['name'] ?? 'Unknown';
                    $level = $player['level'] ?? '0';
                    $ping  = $player['ping'] ?? '0';
                    \printf("  - %s (Level: %s, Ping: %s)\n", (string) $name, (string) $level, (string) $ping);
                }
            }
        }

        if ($server->rules !== []) {
            print 'Server Rules/Variables:' . \PHP_EOL;

            foreach ($server->rules as $key => $value) {
                \printf("  %s: %s\n", (string) $key, (string) $value);
            }
        }
    } else {
        print 'Server is offline.' . \PHP_EOL;
    }
} catch (Exception $e) {
    print 'Error: ' . $e->getMessage() . \PHP_EOL;
}
