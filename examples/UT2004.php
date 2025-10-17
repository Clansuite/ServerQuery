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

use Clansuite\ServerQuery\ServerProtocols\Ut2k4;

$config = [
    'server_addr'     => '74.91.113.72',
    'server_port'     => 7777, // query port
    'request_timeout' => 2,
];

$server = new Ut2k4($config['server_addr'], $config['server_port']);

if ($server->query_server(true, true)) {
    print "✅ Server is online!\n\n";
    print "\nℹ️  Server Information:\n";
    print 'Server Title: ' . ($server->servertitle ?? '') . \PHP_EOL;
    print 'Map: ' . ($server->mapname ?? '') . \PHP_EOL;
    print 'Players: ' . ($server->numplayers ?? 0) . ' / ' . ($server->maxplayers ?? 0) . \PHP_EOL;
    print 'Game Version: ' . ($server->gameversion ?? '') . \PHP_EOL;
    print 'Rules: ' . \json_encode($server->rules) . \PHP_EOL;

    if ($server->players !== [] && \is_array($server->players)) {
        print "\nℹ️  Players:\n";

        foreach ($server->players as $i => $player) {
            $idx   = $i + 1;
            $name  = \is_scalar($player['name'] ?? '') ? (string) ($player['name'] ?? '') : '';
            $score = \is_scalar($player['score'] ?? 0) ? (string) ($player['score'] ?? 0) : '0';
            $ping  = \is_scalar($player['ping'] ?? 0) ? (string) ($player['ping'] ?? 0) : '0';
            \printf(" %2d. %-28s  score:%5s  ping:%3s\n", $idx, $name, $score, $ping);
        }
    }
} else {
    print '❌ Failed: ' . ($server->errstr ?? 'unknown') . \PHP_EOL;
    \print_r($server->debug);
}
