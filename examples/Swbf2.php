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

use Clansuite\ServerQuery\ServerProtocols\Swbf2;

// Example server for Star Wars Battlefront 2
// Find real servers at:
// https://www.gametracker.com/search/star+wars+battlefront+2/
// Note: SWBF2 uses the same protocol as Battlefield 4
$server = '127.0.0.1'; // Replace with real server IP
$port   = 25200; // Default SWBF2 port, replace with real server port

print "Querying Star Wars Battlefront 2 server {$server}:{$port}\n";

$q = new Swbf2($server, $port);

if ($q->query_server(true, true)) {
    print "Server online:\n";
    print ' Title: ' . ($q->servertitle ?? '') . "\n";
    print ' Map: ' . ($q->mapname ?? '') . "\n";
    print ' Game Type: ' . ($q->gametype ?? '') . "\n";
    print ' Players: ' . ($q->numplayers ?? 0) . ' / ' . ($q->maxplayers ?? 0) . "\n";
    print ' Version: ' . ($q->gameversion ?? '') . "\n";
    print ' Rules: ' . \json_encode($q->rules) . "\n";

    if ($q->players !== [] && \is_array($q->players)) {
        print "\nPlayer list:\n";

        foreach ($q->players as $i => $p) {
            if (\is_array($p)) {
                $idx   = $i + 1;
                $name  = $p['name'] ?? $p['playerName'] ?? 'unknown';
                $score = $p['score'] ?? $p['kills'] ?? '0';
                $team  = $p['team'] ?? $p['teamId'] ?? '';
                $squad = $p['squad'] ?? $p['squadId'] ?? '';
                $ping  = $p['ping'] ?? '0';
                \printf(" %2d. %-28s  score:%5s  team:%3s  squad:%3s  ping:%3s\n", $idx, (string) $name, (string) $score, (string) $team, (string) $squad, (string) $ping);
            }
        }
    }
} else {
    print 'Query failed: ' . ($q->errstr ?? 'unknown') . "\n";
}
