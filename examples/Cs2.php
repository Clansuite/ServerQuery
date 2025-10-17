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

use Clansuite\ServerQuery\ServerProtocols\Cs2;

// Test the CS2 server: 78.46.48.169:23232
// https://cs2browser.clash.gg/gamemode/public?maps=de_dust2&locations=DE&regions=EU
$config = [
    'server_addr'     => '78.46.48.169',
    'server_port'     => 23232,
    'request_timeout' => 2,
    'query_protocol'  => 'Cs2',
];

$server = new Cs2($config['server_addr'], $config['server_port']);
print "🔍 Querying {$config['server_addr']}:{$config['server_port']} ({$config['query_protocol']})...\n\n";

if ($server->query_server()) {
    print "✅ Server is online!\n\n";
    print "\nℹ️  Server Information:\n";
    print 'Server Name: ' . $server->servertitle . "\n";
    print 'Map: ' . $server->mapname . "\n";
    print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . "\n";
    print 'Game Type: ' . $server->gametype . "\n";
    print 'Version: ' . $server->gameversion . "\n";

    // Always show player section
    print "\nℹ️ Player List:\n";

    if ($server->players !== []) {
        foreach ($server->players as $player) {
            $name  = \is_scalar($player['name'] ?? 'Unknown') ? (string) ($player['name'] ?? 'Unknown') : 'Unknown';
            $score = \is_scalar($player['score'] ?? '0') ? (string) ($player['score'] ?? '0') : '0';
            $time  = \is_scalar($player['time'] ?? '0') ? (string) ($player['time'] ?? '0') : '0';
            \printf("- %s (Score: %s, Time: %s)\n", $name, $score, $time);
        }
    } else {
        print "No detailed player information available\n";
        print "(Server may have player queries disabled for privacy, or no players are connected)\n";
    }

    // CS2 does not provide rules information
    print "\nℹ️ Rules: Not available (CS2 does not respond to A2S_RULES queries)\n";

    // Show native join URI for CS2
    $joinUri = $server->getNativeJoinURI();

    if ($joinUri !== '' && $joinUri !== '0') {
        print "\n🔗 Steam Join URI: {$joinUri}\n";
    }
} else {
    print '❌ Failed to query server: ' . $server->errstr . "\n";
    print "\n💡 Note: CS2 servers may not be publicly available yet, or the server may be offline.\n";
}

print "\n🔧 Debug info (shows all query attempts made):\n";
\print_r($server->debug);
