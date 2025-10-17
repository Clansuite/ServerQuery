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

use Clansuite\ServerQuery\ServerProtocols\Deadside;

// Example server IP and port
$server = '127.0.0.1'; // Replace with real server
$port   = 27015;

print "Querying DEADSIDE server {$server}:{$port}\n";

$q = new Deadside($server, $port);

if ($q->query_server(true, true)) {
    print "Server online:\n";
    print ' Title: ' . ($q->servertitle ?? '') . "\n";
    print ' Map: ' . ($q->mapname ?? '') . "\n";
    print ' Players: ' . ($q->numplayers ?? 0) . ' / ' . ($q->maxplayers ?? 0) . "\n";
    print ' Rules: ' . \json_encode($q->rules) . "\n";

    if ($q->players !== []) {
        print 'Players list (' . \count($q->players) . "):\n";

        foreach (\array_slice($q->players, 0, 5) as $i => $p) {  // show first 5
            $idx   = $i + 1;
            $name  = \is_scalar($p['name'] ?? '') ? (string) ($p['name'] ?? '') : '';
            $score = \is_scalar($p['score'] ?? 0) ? (int) ($p['score'] ?? 0) : 0;
            $ping  = \is_scalar($p['ping'] ?? 0) ? (int) ($p['ping'] ?? 0) : 0;
            \printf(" %2d. %-28s  score:%5d  ping:%3d\n", $idx, $name, $score, $ping);
        }
    }
} else {
    print 'Query failed: ' . ($q->errstr ?? 'unknown') . "\n";
}
