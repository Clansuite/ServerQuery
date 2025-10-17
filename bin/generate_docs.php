#!/usr/bin/env php
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

use Clansuite\ServerQuery\DocumentProtocols;

/**
 * Script to generate protocol documentation in the /docs using DocumentProtocols.
 */
if (\is_file(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    \fwrite(\STDERR, "ERROR: unable to find autoloader\n");

    exit(1);
}

require __DIR__ . '/../src/CSQuery/DocumentProtocols.php';

$doc = new DocumentProtocols;
$doc->parseProtocols();
$doc->writeFiles(__DIR__ . '/../docs');
