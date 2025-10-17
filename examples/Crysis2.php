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

use Clansuite\ServerQuery\ServerProtocols\Crysis2;

// Example server for Crysis 2 (note: Crysis 2 servers are rare)
// Server IP: example.com, Port: 64087

$server = 'example.com';
$port   = 64087;

print "Querying Crysis 2 server {$server}:{$port}\n";

$q = new Crysis2($server, $port);

if ($q->query_server(true, true)) {
    print "Server online:\n";
    print ' Title: ' . ($q->servertitle ?? '') . "\n";
    print ' Map: ' . ($q->mapname ?? '') . "\n";
    print ' Players: ' . ($q->numplayers ?? 0) . ' / ' . ($q->maxplayers ?? 0) . "\n";
    print ' Rules: ' . \json_encode($q->rules) . "\n";

    if ($q->players !== []) {
        print 'Players list (' . \count($q->players) . "):\n";

        foreach (\array_slice($q->players, 0, 5) as $p) {  // show first 5
            $name  = \is_scalar($p['name'] ?? 'Unknown') ? (string) ($p['name'] ?? 'Unknown') : 'Unknown';
            $score = \is_scalar($p['score'] ?? 0) ? (int) ($p['score'] ?? 0) : 0;
            \printf("  - %s (Score: %d)\n", $name, $score);
        }
    } else {
        print "  No players found.\n";
    }
} else {
    print 'Query failed: ' . ($q->errstr ?? 'unknown') . "\n";
}
