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

use Clansuite\ServerQuery\ServerProtocols\Cod4;

$config = [
    'server_addr'     => '84.235.240.78',
    'server_port'     => 28960, // query port (same as game port for Quake 3 based)
    'request_timeout' => 2,
];

$server = new Cod4($config['server_addr'], $config['server_port']);

print "Querying Call of Duty 4 server {$config['server_addr']}:{$config['server_port']}...\n";

if ($server->query_server(true, true)) {  // enable players and rules
    print 'Server: ' . $server->servertitle . "\n";
    print 'Map: ' . $server->mapname . "\n";
    print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . "\n";

    if ($server->players !== []) {
        print \PHP_EOL . 'Players:' . \PHP_EOL;

        foreach ($server->players as $player) {
            $name  = \is_scalar($player['name'] ?? 'Unknown') ? (string) ($player['name'] ?? 'Unknown') : 'Unknown';
            $score = \is_scalar($player['score'] ?? 0) ? (int) ($player['score'] ?? 0) : 0;
            $ping  = \is_scalar($player['ping'] ?? 0) ? (int) ($player['ping'] ?? 0) : 0;
            \printf('- %s (Score: %d, Ping: %d)%s', $name, $score, $ping, \PHP_EOL);
        }
    } else {
        print "No players list available\n";
    }
} else {
    print "Failed to query server\n";
}
