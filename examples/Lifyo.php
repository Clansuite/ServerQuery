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

use Clansuite\ServerQuery\ServerProtocols\Lifyo;

// Example server for Life Is Feudal
// Find real servers at:
// https://life-is-feudal.org/servers/updated/
// https://www.trackyserver.com/de/life-is-feudal-server
$server = '148.113.198.37'; // Replace with real server IP
$port   = 27036; // Replace with real server port

print "Querying Life Is Feudal server {$server}:{$port}\n";

$q = new Lifyo($server, $port);

if ($q->query_server(true, true)) {
    print "Server online:\n";
    print ' Title: ' . ($q->servertitle ?? '') . "\n";
    print ' Map: ' . ($q->mapname ?? '') . "\n";
    print ' Players: ' . ($q->numplayers ?? 0) . ' / ' . ($q->maxplayers ?? 0) . "\n";
    print ' Rules: ' . \json_encode($q->rules) . "\n";

    if ($q->players !== []) {
        print 'Players list (' . \count($q->players) . "):\n";

        foreach (\array_slice($q->players, 0, 5) as $p) {  // show first 5
            $name  = \is_scalar($p['name'] ?? '(unknown)') ? (string) ($p['name'] ?? '(unknown)') : '(unknown)';
            $score = \is_scalar($p['score'] ?? 0) ? (int) ($p['score'] ?? 0) : 0;
            $time  = \is_scalar($p['time'] ?? '0') ? (string) ($p['time'] ?? '0') : '0';
            \printf(" - %s (score: %d, time: %s)\n", $name, $score, $time);
        }

        if (\count($q->players) > 5) {
            print ' ... and ' . (\count($q->players) - 5) . " more\n";
        }
    } else {
        print "Player list not available.\n";
    }

    if ($q->rules !== []) {
        print 'Rules count: ' . \count($q->rules) . "\n";
    }
} else {
    print 'Query failed: ' . ($q->errstr ?? 'unknown') . "\n";
}
