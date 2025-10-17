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

use Clansuite\ServerQuery\ServerProtocols\GtaMta;

$config = [
    'server_addr'     => '88.99.217.245',
    'server_port'     => 22126, // query port (game port + 123)
    'request_timeout' => 2,
];

$server = new GtaMta($config['server_addr'], $config['server_port']);
print "Querying Multi Theft Auto server {$config['server_addr']}:{$config['server_port']}...\n";

// Query server (request players and rules)
if (!$server->query_server(true, true)) {
    print 'Failed to query server: ' . ($server->errstr ?? 'unknown error') . "\n";

    exit(1);
}

// Required outputs
// Server name
$serverName = $server->servertitle ?? ($server->rules['hostname'] ?? '');
$name       = \is_scalar($serverName) ? (string) $serverName : '';
\printf("Server name: %s\n", $name !== '' ? $name : '(unknown)');

// Current map
$map    = $server->mapname ?? ($server->rules['map'] ?? '(unknown)');
$mapStr = \is_scalar($map) ? (string) $map : '(unknown)';
\printf("Current map: %s\n", $mapStr);

// Player count
print 'Player count: ' . $server->numplayers . '/' . $server->maxplayers . "\n";

// Player list
if ($server->players !== []) {
    print 'Player list (' . \count($server->players) . "):\n";

    foreach ($server->players as $player) {
        $name = \is_scalar($player['name'] ?? ($player[0] ?? '(unknown)')) ? (string) ($player['name'] ?? ($player[0] ?? '(unknown)')) : '(unknown)';
        \printf(" - %s\n", $name);
    }
} else {
    print "Player list: (none)\n";
}
