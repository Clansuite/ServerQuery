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

use Clansuite\ServerQuery\ServerProtocols\Mohw;

// Example server for Medal of Honor Warfighter (note: servers are rare)
// Server IP: example.com, Port: 25200

$server = 'example.com';
$port   = 25200;

print "Querying Medal of Honor Warfighter server {$server}:{$port}\n";

$q = new Mohw($server, $port);

if ($q->query_server(true, true)) {
    print "Server online:\n";
    print ' Title: ' . ($q->servertitle ?? '') . "\n";
    print ' Map: ' . ($q->mapname ?? '') . "\n";
    print ' Players: ' . ($q->numplayers ?? 0) . ' / ' . ($q->maxplayers ?? 0) . "\n";
    print ' Rules: ' . \json_encode($q->rules) . "\n";

    if ($q->players !== [] && \is_array($q->players)) {
        print "\nPlayer list:\n";

        foreach ($q->players as $i => $p) {
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
