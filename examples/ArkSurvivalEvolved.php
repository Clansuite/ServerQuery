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

use Clansuite\ServerQuery\ServerProtocols\ArkSurvivalEvolved;

// 136.243.23.31:27046 (Game Port)
// 136.243.23.31:27045 (Query Port)
// $server = '136.243.23.31';
// $port   = 27045;
$server = '176.31.226.111';
$port   = 27015;

print "Querying Ark SE server {$server}:{$port}\n";

$q = new ArkSurvivalEvolved($server, $port, false);

if ($q->query_server(true, true)) {
    print "Server online:\n";
    print ' Title: ' . ($q->servertitle ?? '') . "\n";
    print ' Map: ' . ($q->mapname ?? '') . "\n";
    print ' Players: ' . ($q->numplayers ?? 0) . ' / ' . ($q->maxplayers ?? 0) . "\n";
    print ' Rules: ' . \json_encode($q->rules) . "\n";

    if ($q->players !== []) {
        print 'Players list (' . \count($q->players) . "):\n";

        foreach (\array_slice($q->players, 0, 5) as $p) {  // show first 5
            $name  = $p['name'] ?? '(unknown)';
            $score = $p['score'] ?? 0;
            $time  = $p['time'] ?? '0';
            $name  = \is_scalar($name) ? (string) $name : '(unknown)';
            $score = \is_scalar($score) ? (string) $score : '0';
            $time  = \is_scalar($time) ? (string) $time : '0';
            print ' - ' . $name . ' (score: ' . $score . ', time: ' . $time . ")\n";
        }

        if (\count($q->players) > 5) {
            print ' ... and ' . (\count($q->players) - 5) . " more\n";
        }
    } else {
        print "Player list not available.\n";
    }

    if ($q->rules !== []) {
        print 'Rules count: ' . \count($q->rules) . "\n";
    }
} else {
    print 'Query failed: ' . ($q->errstr ?? 'unknown') . "\n";
}
