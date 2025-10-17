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

require __DIR__ . '/../vendor/autoload.php';

use Clansuite\Capture\ServerAddress;
use Clansuite\ServerQuery\ServerProtocols\Ventrilo;

// Simple CLI: examples/Ventrilo.php [address] [port] [--fixture]
$args = $argv;
\array_shift($args); // drop script name

$fixtureMode = false;
$address     = '78.129.193.68';
$port        = 3808;

foreach ($args as $a) {
    if ($a === '--fixture' || $a === '-f') {
        $fixtureMode = true;

        continue;
    }

    if (\filter_var($a, \FILTER_VALIDATE_IP) !== false) {
        $address = $a;

        continue;
    }

    if (\is_numeric($a)) {
        $port = (int) $a;

        continue;
    }
}

if ($fixtureMode) {
    $fixture = __DIR__ . '/../tests/fixtures/ventrilo/capture_78_129_193_68_3808.json';
    print "Using fixture: {$fixture}\n";

    if (!\is_file($fixture) || !\is_readable($fixture)) {
        print "Fixture not found or unreadable: {$fixture}\n";

        exit(2);
    }

    $json = \file_get_contents($fixture);

    if ($json === false) {
        print "Failed to read fixture: {$fixture}\n";

        exit(2);
    }

    $data = \json_decode($json, true);
    $key  = "{$address}:{$port}";

    if (!\is_array($data)) {
        $data = [];
    }

    // Try to find an entry that matches the provided address:port, else use the first entry
    if (isset($data[$key])) {
        $entry = $data[$key];
    } else {
        // pick first
        $entry = \reset($data);
    }

    if (!\is_array($entry)) {
        $entry = [];
    }

    $name    = (string) ($entry['name'] ?? $entry['gq_name'] ?? $address);
    $clients = $entry['clientcount'] ?? (\is_array($entry['players'] ?? null) ? \count($entry['players']) : 0);
    $max     = (string) ($entry['maxclients'] ?? 0);

    print "Server: {$name}\n";
    print "Players: {$clients}/{$max}\n";
    print "--- PLAYERS ---\n";

    $players = $entry['players'] ?? [];

    if (\is_array($players)) {
        foreach ($players as $p) {
            if (\is_array($p)) {
                $pname = (string) ($p['name'] ?? 'unknown');
                print ' - ' . $pname . "\n";
            }
        }
    }

    exit(0);
}

print "Querying Ventrilo server {$address}:{$port}...\n";

$proto = new Ventrilo($address, $port);
$info  = $proto->query(new ServerAddress($address, $port));

if ($info->online) {
    $servertitle = $info->servertitle ?? $address;
    $numplayers  = $info->numplayers ?? 0;
    $maxplayers  = $info->maxplayers ?? 0;

    print "Server: {(string) {$servertitle}}\n";
    print "Players: {(string) {$numplayers}}/{(string) {$maxplayers}}\n";
    print "--- PLAYERS ---\n";

    $players = $info->players ?? [];

    foreach ($players as $p) {
        if (\is_array($p)) {
            $pname = (string) ($p['name'] ?? 'unknown');
            print ' - ' . $pname . "\n";
        }
    }
} else {
    print 'Server appears offline: ' . ($info->errstr ?? 'unknown error') . "\n";
}
