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

use Clansuite\ServerQuery\CSQuery;

$factory = new CSQuery;
$config  = [
    'server_addr'    => '127.0.0.1',
    'server_port'    => 27015,
    'query_protocol' => 'Czero',
];

$server = $factory->createInstance($config['query_protocol'], $config['server_addr'], $config['server_port']);
print "Querying {$config['server_addr']}:{$config['server_port']} ({$config['query_protocol']})...\n";

if ($server->query_server()) {
    print 'Server: ' . $server->servertitle . "\n";
    print 'Map: ' . $server->mapname . "\n";
    print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . "\n";
} else {
    print 'Failed to query server: ' . $server->errstr . "\n";
}

print "\nDebug:\n";
\print_r($server->debug);
