<?php

declare(strict_types=1);

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

use Clansuite\ServerQuery\ServerProtocols\Minecraft;

// https://www.gametracker.com/search/minecraft/
$config = [
    'server_addr'     => '208.98.42.100',
    'server_port'     => 25565, // query port
    'request_timeout' => 2,
];

$server = new Minecraft($config['server_addr'], $config['server_port'], 'slp'); // or 'legacy'

print "Querying Minecraft server {$config['server_addr']}:{$config['server_port']} using {$server->protocolVersion} protocol...\n";

if ($server->query_server(true, true)) {  // enable players and rules
    print 'Server: ' . $server->servertitle . "\n";
    print 'Version: ' . $server->gameversion . "\n";
    print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . "\n";

    print "\n--- PLAYERS ---\n";

    if ($server->players !== []) {
        print 'Players list (' . \count($server->players) . "):\n";

        foreach (\array_slice($server->players, 0, 5) as $p) {  // show first 5
            $name  = \is_scalar($p['name'] ?? '(unknown)') ? (string) ($p['name'] ?? '(unknown)') : '(unknown)';
            $score = \is_scalar($p['score'] ?? 0) ? (int) ($p['score'] ?? 0) : 0;
            $time  = \is_scalar($p['time'] ?? '0') ? (string) ($p['time'] ?? '0') : '0';
            \printf(" - %s (score: %d, time: %s)\n", $name, $score, $time);
        }

        if (\count($server->players) > 5) {
            print ' ... and ' . (\count($server->players) - 5) . " more\n";
        }
    } else {
        print "Player list not available.\n";
    }

    print "\n--- SERVER VARIABLES ---\n";

    if ($server->rules !== []) {
        print "Server Rules:\n";

        foreach ($server->rules as $key => $value) {
            $val = \is_scalar($value) ? (string) $value : '';
            \printf("  - %s: %s\n", $key, $val);
        }

        print 'Rules count: ' . \count($server->rules) . "\n";
    } else {
        print "No server rules available\n";
    }

    // Debug: Show all available properties
    print "\n--- DEBUG INFO ---\n";
    print 'Players array count: ' . \count($server->players) . "\n";
    print 'Rules array count: ' . \count($server->rules) . "\n";
    print 'Online: ' . ($server->online ? 'true' : 'false') . "\n";
    print 'Error: ' . $server->errstr . "\n";
} else {
    print 'Query failed: ' . $server->errstr . "\n";
}
