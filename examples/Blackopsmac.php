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

use Clansuite\ServerQuery\ServerProtocols\Blackopsmac;

// Example server for Call of Duty: Black Ops Mac
// Find real servers at:
// https://www.gametracker.com/search/call+of+duty+black+ops/
// Note: COD Black Ops Mac uses the Quake 3 protocol
$server = '127.0.0.1'; // Replace with real server IP
$port   = 28960; // Default COD Black Ops port, replace with real server port

print "Querying Call of Duty: Black Ops Mac server {$server}:{$port}\n";

$q = new Blackopsmac($server, $port);

if ($q->query_server(true, true)) {
    print "Server online:\n";
    print ' Title: ' . ($q->servertitle ?? '') . "\n";
    print ' Map: ' . ($q->mapname ?? '') . "\n";
    print ' Game Type: ' . ($q->gametype ?? '') . "\n";
    print ' Players: ' . ($q->numplayers ?? 0) . ' / ' . ($q->maxplayers ?? 0) . "\n";
    print ' Version: ' . ($q->gameversion ?? '') . "\n";
    print ' Rules: ' . \json_encode($q->rules) . "\n";

    if ($q->players !== []) {
        print "\nPlayer list:\n";

        foreach ($q->players as $i => $p) {
            $idx   = $i + 1;
            $name  = $p['name'] ?? '';
            $score = $p['score'] ?? 0;
            $ping  = $p['ping'] ?? 0;
            $name  = \is_scalar($name) ? (string) $name : '';
            $score = \is_scalar($score) ? (string) $score : '0';
            $ping  = \is_scalar($ping) ? (string) $ping : '0';
            \printf(" %2d. %-28s  score:%5s  ping:%3s\n", $idx, $name, $score, $ping);
        }
    }
} else {
    print 'Query failed: ' . ($q->errstr ?? 'unknown') . "\n";
}
