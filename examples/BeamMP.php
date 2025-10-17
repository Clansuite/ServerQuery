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

use Clansuite\ServerQuery\ServerProtocols\BeamMP;

$config = [
    'server_addr'     => '23.88.73.88',
    'server_port'     => 34127,
    'request_timeout' => 5,
];

$server = new BeamMP($config['server_addr'], $config['server_port']);

print "Querying BeamMP server {$config['server_addr']}:{$config['server_port']}...\n";

if ($server->query_server(true, true)) {
    print 'Server: ' . ($server->servertitle ?? '(unknown)') . "\n";
    print 'Map: ' . ($server->mapname ?? '') . "\n";
    print 'Players: ' . ($server->numplayers ?? 0) . '/' . ($server->maxplayers ?? 0) . "\n";

    if ($server->players !== []) {
        print "\n--- PLAYERS ---\n";

        foreach ($server->players as $p) {
            $name = $p['name'] ?? '(unknown)';
            $name = \is_scalar($name) ? (string) $name : '(unknown)';
            print ' - ' . $name . "\n";
        }
    }
    print "\n--- SERVER VARIABLES / RULES ---\n";

    if ($server->rules !== []) {
        foreach ($server->rules as $k => $v) {
            if ($k === 'mods' && \is_array($v)) {
                print " - mods:\n";

                foreach ($v as $mod) {
                    $mod = \is_scalar($mod) ? (string) $mod : '';
                    print "    * {$mod}\n";
                }

                continue;
            }

            print " - {$k}: " . (\is_bool($v) ? ($v ? 'true' : 'false') : (\is_scalar($v) ? (string) $v : '')) . "\n";
        }
    } else {
        print "No server variables available\n";
    }

    print "\nError: " . ($server->errstr ?? '') . "\n";
} else {
    print 'Failed to query server: ' . ($server->errstr ?? 'unknown error') . "\n";
}
