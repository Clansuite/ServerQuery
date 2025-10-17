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

use Clansuite\ServerQuery\ServerProtocols\Bf1942;

$config = [
    'server_addr'     => '108.61.119.37',
    'server_port'     => 15567, // query port
    'request_timeout' => 2,
];

$server = new Bf1942($config['server_addr'], $config['server_port']);

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
            $name  = $player['name'] ?? 'Unknown';
            $score = $player['score'] ?? 0;
            $ping  = $player['ping'] ?? 0;
            $name  = \is_scalar($name) ? (string) $name : 'Unknown';
            $score = \is_scalar($score) ? (string) $score : '0';
            $ping  = \is_scalar($ping) ? (string) $ping : '0';
            print ' - ' . $name . ' (Score: ' . $score . ', Ping: ' . $ping . ')' . \PHP_EOL;
        }
    }

    if ($server->rules !== []) {
        print "\nℹ️  Server Rules:\n";

        foreach ($server->rules as $key => $value) {
            $value = \is_scalar($value) ? (string) $value : '';
            print ' - ' . $key . ': ' . $value . \PHP_EOL;
        }
    }
} else {
    print '❌ Failed: ' . $server->errstr . \PHP_EOL;
    \print_r($server->debug);
}
