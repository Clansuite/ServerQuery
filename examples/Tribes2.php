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

use Clansuite\ServerQuery\ServerProtocols\Tribes2;

// Example server for Tribes 2
// Find real servers at:
// https://www.gametracker.com/search/tribes+2/
// https://tribesnext.com/servers/
$server = '185.66.108.39'; // Real Tribes 2 server
$port   = 28000; // Tribes 2 server port

print "Querying Tribes 2 server {$server}:{$port}\n";

$q = new Tribes2($server, $port);

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
            $idx   = $i + 1;
            $name  = \is_scalar($p['name'] ?? '') ? (string) ($p['name'] ?? '') : '';
            $score = \is_scalar($p['score'] ?? 0) ? (string) ($p['score'] ?? 0) : '0';
            $ping  = \is_scalar($p['ping'] ?? 0) ? (string) ($p['ping'] ?? 0) : '0';
            \printf(" %2d. %-28s  score:%5s  ping:%3s\n", $idx, $name, $score, $ping);
        }
    }
} else {
    print 'Query failed: ' . ($q->errstr ?? 'unknown') . "\n";
}
