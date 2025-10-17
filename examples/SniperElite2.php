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

use Clansuite\ServerQuery\ServerProtocols\SniperElite2;

$config = [
    'server_addr'     => '198.244.169.95', // Alternative community server
    'server_port'     => 37013, // Unreal2 query port (game port 37012 + 1)
    'request_timeout' => 2,
];

$server = new SniperElite2($config['server_addr'], $config['server_port']);

print "Querying Sniper Elite V2 server {$config['server_addr']}:{$config['server_port']}...\n";

if ($server->query_server(true, true)) {  // enable players and rules
    print 'Server: ' . $server->servertitle . "\n";
    print 'Map: ' . $server->mapname . "\n";
    print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . "\n";

    if ($server->players !== []) {
        print 'Players list (' . \count($server->players) . "):\n";

        foreach (\array_slice($server->players, 0, 5) as $p) {  // show first 5
            $name  = \is_scalar($p['name'] ?? '(unknown)') ? (string) ($p['name'] ?? '(unknown)') : '(unknown)';
            $score = \is_scalar($p['score'] ?? 0) ? (int) ($p['score'] ?? 0) : 0;
            \printf(" - %s (score: %d)\n", $name, $score);
        }

        if (\count($server->players) > 5) {
            print ' ... and ' . (\count($server->players) - 5) . " more players\n";
        }
    } else {
        print "No players online\n";
    }

    if ($server->rules !== []) {
        print "\nServer rules (" . \count($server->rules) . "):\n";

        foreach ($server->rules as $key => $value) {
            $val = \is_scalar($value) ? (string) $value : '';
            \printf(" - %s: %s\n", $key, $val);
        }
    }
} else {
    print "Failed to query server. Server may be offline or unreachable.\n";
    print 'Error: ' . ($server->errstr ?? 'Unknown error') . "\n";
}

print "Note: While GameTracker shows this server as active with players, it may not respond to query requests.\n";
print "This could be due to firewall settings or the server using a different query port.\n\n";
