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

/**
 * Script to capture and store a gameserver query result as fixture.
 */
if (\is_file(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    \fwrite(\STDERR, "ERROR: unable to find autoloader\n");

    exit(1);
}

require __DIR__ . '/../src/Capture/bootstrap_capture.php';

use function Clansuite\Capture\createCaptureService;
use Clansuite\Capture\CLI\CaptureCommand;
use Clansuite\Capture\CLI\HelpCommand;
use Clansuite\Capture\CLI\ListCapturesCommand;
use Clansuite\Capture\Storage\JsonFixtureStorage;

$argv = $_SERVER['argv'] ?? ['capture'];

if (!\is_array($argv)) {
    $argv = ['capture'];
}

/** @var array<string> $argv */
$argv0 = \is_string($argv[0] ?? null) ? $argv[0] : 'capture';
$arg1  = \is_string($argv[1] ?? null) ? $argv[1] : null;

// Help handling at top-level: delegate to central HelpCommand
if (\in_array($arg1, ['-h', '--help', 'help'], true) || $arg1 === null) {
    $help = new HelpCommand;

    exit($help->run(['', $arg1 ?? '']));
}

// If user asked for list of fixtures
if (\in_array($arg1, ['list', 'listcaptures', 'ls'], true)) {
    $config = require __DIR__ . '/../config/capture_config.php';

    if (!\is_array($config)) {
        exit(1);
    }

    $fixturesDir = \is_string($config['fixtures_dir'] ?? null) ? $config['fixtures_dir'] : '';
    $storage     = new JsonFixtureStorage($fixturesDir);

    $listCmd = new ListCapturesCommand($storage);

    exit($listCmd->run($argv));
}

// Otherwise fall back to capture behavior (backward compatible)
$captureService = createCaptureService();
$command        = new CaptureCommand($captureService);

// Execute the command and capture output
exit($command->run($argv));
