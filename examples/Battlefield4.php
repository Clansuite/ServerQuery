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

use Clansuite\ServerQuery\ServerProtocols\Battlefield4;

$server = '94.250.199.27';
$port   = 25200; // client port; query port may be client+22000 for BF4

print "Querying BF4 server {$server}:{$port}\n";

$q = new Battlefield4($server, $port);

if ($q->query_server(true, true)) {
    print "Server online:\n";
    print ' Title: ' . ($q->servertitle ?? '') . "\n";
    print ' Map: ' . ($q->mapname ?? '') . "\n";
    print ' Players: ' . ($q->numplayers ?? 0) . ' / ' . ($q->maxplayers ?? 0) . "\n";

    if ($q->players !== []) {
        print "\nPlayer list:\n";

        foreach ($q->players as $i => $p) {
            $idx      = $i + 1;
            $nameTmp  = $p['name'] ?? $p['playerName'] ?? 'unknown';
            $scoreTmp = $p['score'] ?? ($p['kills'] ?? 0);
            $teamTmp  = $p['team'] ?? ($p['teamId'] ?? '');
            $squadTmp = $p['squad'] ?? ($p['squadId'] ?? '');
            $pingTmp  = $p['ping'] ?? 0;
            $name     = \is_scalar($nameTmp) ? (string) $nameTmp : 'unknown';
            $score    = \is_scalar($scoreTmp) ? (string) $scoreTmp : '0';
            $team     = \is_scalar($teamTmp) ? (string) $teamTmp : '';
            $squad    = \is_scalar($squadTmp) ? (string) $squadTmp : '';
            $ping     = \is_scalar($pingTmp) ? (string) $pingTmp : '0';
            \printf(" %2d. %-28s  score:%5s  team:%3s  squad:%3s  ping:%3s\n", $idx, $name, $score, $team, $squad, $ping);
        }
    }
} else {
    print 'Query failed: ' . ($q->errstr ?? 'unknown') . "\n";
}
