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

use Clansuite\ServerQuery\ServerProtocols\Csgo;

// Test the CS:GO server
$config = [
    'server_addr'     => '202.181.188.156',
    'server_port'     => 27015,
    'request_timeout' => 2,
    'query_protocol'  => 'Csgo',
];

$server = new Csgo($config['server_addr'], $config['server_port']);
print "🔍 Querying {$config['server_addr']}:{$config['server_port']} ({$config['query_protocol']})...\n\n";

if ($server->query_server()) {
    print "✅ Server is online!\n\n";
    print "\nℹ️  Server Information:\n";
    print 'Server Name: ' . $server->servertitle . "\n";
    print 'Map: ' . $server->mapname . "\n";
    print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . "\n";
    print 'Game Type: ' . $server->gametype . "\n";
    print 'Version: ' . $server->gameversion . "\n";

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

    if ($server->rules !== []) {
        print 'Rules count: ' . \count($server->rules) . "\n";
    }

    // Show native join URI for CS:GO
    $joinUri = $server->getNativeJoinURI();

    if ($joinUri !== '' && $joinUri !== '0') {
        print "\n🔗 Steam Join URI: {$joinUri}\n";
    }
} else {
    print '❌ Failed to query server: ' . $server->errstr . "\n";
}

print "\n🔧 Debug info (shows all query attempts made):\n";
\print_r($server->debug);
