#!/usr/bin/env php
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

use Clansuite\ServerQuery\CSQuery;

/**
 * Script to serialize a gameserver query result to JSON or HTML.
 */
if (\is_file(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    \fwrite(\STDERR, "ERROR: unable to find autoloader\n");

    exit(1);
}

if (isset($_GET['format']) && $_GET['format'] === 'html') {
    \header('Content-type: text/html', true);
} else {
    \header('Content-type: application/json', true);
}

$protocol  = $_GET['protocol'] ?? null;
$host      = $_GET['host'] ?? null;
$queryport = $_GET['queryport'] ?? null;

if ($protocol === null || $host === null || $queryport === null) {
    if (isset($_GET['format']) && $_GET['format'] === 'html') {
        print '<!DOCTYPE html><html><head><title>Error</title></head><body><h1>Error</h1><p>Missing required parameters: protocol, host, queryport</p></body></html>';
    } else {
        print \json_encode(['error' => 'Missing required parameters: protocol, host, queryport']);
    }

    exit(1);
}

if (!\is_string($protocol) || !\is_string($host) || !\is_numeric($queryport)) {
    if (isset($_GET['format']) && $_GET['format'] === 'html') {
        print '<!DOCTYPE html><html><head><title>Error</title></head><body><h1>Error</h1><p>Invalid parameter types</p></body></html>';
    } else {
        print \json_encode(['error' => 'Invalid parameter types']);
    }

    exit(1);
}

$queryport = (int) $queryport;

$factory    = new CSQuery;
$gameserver = $factory->createInstance($protocol, $host, $queryport);

/** @var CSQuery $gameserver */
$gameserver->query_server(true, true);

if (isset($_GET['format']) && $_GET['format'] === 'html') {
    print $gameserver->toHtml();
} else {
    print $gameserver->toJson();
}
