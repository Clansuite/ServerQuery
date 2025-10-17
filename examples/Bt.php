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

use Clansuite\ServerQuery\ServerProtocols\Bt;

// Example server for Medal of Honor Breakthrough (note: servers are rare)
// Server IP: example.com, Port: 12203

$server = 'example.com';
$port   = 12203;

print "Querying Medal of Honor Breakthrough server {$server}:{$port}\n";

$q = new Bt($server, $port);

if ($q->query_server(true, true)) {
    print "Server online:\n";
    print ' Title: ' . ($q->servertitle ?? '') . "\n";
    print ' Map: ' . ($q->mapname ?? '') . "\n";
    print ' Players: ' . ($q->numplayers ?? 0) . ' / ' . ($q->maxplayers ?? 0) . "\n";
    print ' Rules: ' . \json_encode($q->rules) . "\n";

    if ($q->players !== []) {
        print \PHP_EOL . 'Players:' . \PHP_EOL;

        foreach ($q->players as $player) {
            $name  = \is_scalar($player['name'] ?? 'Unknown') ? (string) ($player['name'] ?? 'Unknown') : 'Unknown';
            $score = \is_scalar($player['score'] ?? 0) ? (int) ($player['score'] ?? 0) : 0;
            $ping  = \is_scalar($player['ping'] ?? 0) ? (int) ($player['ping'] ?? 0) : 0;
            \printf('- %s (Score: %d, Ping: %d)%s', $name, $score, $ping, \PHP_EOL);
        }
    }
} else {
    print 'Query failed: ' . ($q->errstr ?? 'unknown') . "\n";
}
