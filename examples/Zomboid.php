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

$csQuery = new CSQuery;

// Project Zomboid server query - replace with actual server details
$serverAddress = '172.107.179.193';
$queryPort     = 28600;

print "Querying Project Zomboid server at {$serverAddress}:{$queryPort}..." . \PHP_EOL;

$server = $csQuery->createInstance('zomboid', $serverAddress, $queryPort);

if ($server->query_server()) {
    print 'Server is online!' . \PHP_EOL;
    print 'Server Name: ' . $server->servertitle . \PHP_EOL;
    print 'Map: ' . $server->mapname . \PHP_EOL;
    print 'Players: ' . $server->numplayers . '/' . $server->maxplayers . \PHP_EOL;
    print 'Game: ' . $server->gamename . \PHP_EOL;
    print 'Version: ' . $server->gameversion . \PHP_EOL;

    if (isset($server->players) && \is_array($server->players)) {
        print 'Player List:' . \PHP_EOL;

        foreach ($server->players as $player) {
            $name = \is_scalar($player['name'] ?? 'Unknown') ? (string) ($player['name'] ?? 'Unknown') : 'Unknown';
            \printf("- %s\n", $name);
        }
    }
} else {
    print 'Server is offline or unreachable.' . \PHP_EOL;
}
