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

use Clansuite\ServerQuery\ServerProtocols\Bf3;

// https://www.gametracker.com/search/bf3/
$config = [
    'server_addr'     => '94.250.199.152',
    'server_port'     => 25200, // query port
    'request_timeout' => 2,
];

$server = new Bf3($config['server_addr'], $config['server_port']);

print "Querying Battlefield 3 server {$config['server_addr']}:{$config['server_port']}...\n";

if ($server->query_server(true, true)) {  // enable players and rules
    print 'Server: ' . $server->servertitle . "\n";
    print 'Map: ' . $server->mapname . "\n";
    print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . "\n";

    if ($server->rules !== []) {
        print "Server Rules:\n";

        foreach ($server->rules as $key => $value) {
            $value = \is_scalar($value) ? (string) $value : '';
            print "  - {$key}: {$value}\n";
        }
    }

    if ($server->players !== []) {
        print 'Players list (' . \count($server->players) . "):\n";
        // Sort players by kills descending
        \usort($server->players, static fn (array $a, array $b): int => ($b['kills'] ?? 0) <=> ($a['kills'] ?? 0));
        \printf("%-5s %-20s %-8s %-12s\n", 'Rank', 'Name', 'Score', 'Time Played');
        print \str_repeat('-', 50) . "\n";
        $rank = 1;

        foreach ($server->players as $p) {
            $name  = \is_scalar($p['name'] ?? null) ? (string) ($p['name'] ?? 'Unknown') : 'Unknown';
            $kills = \is_scalar($p['kills'] ?? null) ? (int) ($p['kills'] ?? 0) : 0;
            \printf("%-5d %-20s %-8d %-12s\n", $rank, $name, $kills, 'N/A');
            $rank++;
        }

        // Debug: show all fields for first player
        print "\nAll fields for top player:\n";
        \var_dump($server->players[0]);
    } else {
        print "No players list available\n";
    }
} else {
    print 'Failed to query server: ' . ($server->errstr ?? 'unknown error') . "\n";
}
