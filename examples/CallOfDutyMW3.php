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

use Clansuite\ServerQuery\ServerProtocols\Steam;

$config = [
    'server_addr'     => '74.91.113.218',
    'server_port'     => 27015, // query port
    'request_timeout' => 2,
];

$server = new Steam($config['server_addr'], $config['server_port']);

if ($server->query_server()) {
    print "✅ Server is online!\n\n";
    print "\nℹ️  Server Information:\n";
    print 'Server Title: ' . $server->servertitle . \PHP_EOL;
    print 'Map: ' . $server->mapname . \PHP_EOL;
    print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . \PHP_EOL;
    print 'Game Version: ' . $server->gameversion . \PHP_EOL;

    if ($server->players !== []) {
        print "\nℹ️  Players:\n";

        foreach ($server->players as $player) {
            $name  = \is_scalar($player['name'] ?? 'Unknown') ? (string) ($player['name'] ?? 'Unknown') : 'Unknown';
            $score = \is_scalar($player['score'] ?? 0) ? (int) ($player['score'] ?? 0) : 0;
            $ping  = \is_scalar($player['ping'] ?? 0) ? (int) ($player['ping'] ?? 0) : 0;
            \printf(' - %s (Score: %d, Ping: %d)%s', $name, $score, $ping, \PHP_EOL);
        }
    }

    if ($server->rules !== []) {
        print "\nℹ️  Server Rules:\n";

        foreach ($server->rules as $key => $value) {
            $val = \is_scalar($value) ? (string) $value : '';
            \printf(' - %s: %s%s', $key, $val, \PHP_EOL);
        }
    }
} else {
    print '❌ Failed: ' . $server->errstr . \PHP_EOL;
    \print_r($server->debug);
}
