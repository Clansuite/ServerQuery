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

use Clansuite\ServerQuery\ServerProtocols\Codbo3;

// Example server for Call of Duty: Black Ops 3
// Find real servers at:
// https://www.gametracker.com/search/call+of+duty+black+ops+3/
// Note: COD BO3 uses the Steam A2S protocol
$server = '127.0.0.1'; // Replace with real server IP
$port   = 27015; // Default COD BO3 port, replace with real server port

print "Querying Call of Duty: Black Ops 3 server {$server}:{$port}\n";

$q = new Codbo3($server, $port);

if ($q->query_server(true, true)) {
    print "Server online:\n";
    print ' Title: ' . ($q->servertitle ?? '') . "\n";
    print ' Map: ' . ($q->mapname ?? '') . "\n";
    print ' Game Type: ' . ($q->gametype ?? '') . "\n";
    print ' Players: ' . ($q->numplayers ?? 0) . ' / ' . ($q->maxplayers ?? 0) . "\n";
    print ' Version: ' . ($q->gameversion ?? '') . "\n";
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
