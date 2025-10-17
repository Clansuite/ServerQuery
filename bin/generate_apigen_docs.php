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
 * Script to generate API documentation using ApiGen.
 */
if (\is_file(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    \fwrite(\STDERR, "ERROR: unable to find autoloader\n");

    exit(1);
}

// Create build directory if it doesn't exist
$buildDir = __DIR__ . '/../build/apigen';

if (!\is_dir(\dirname($buildDir))) {
    \mkdir(\dirname($buildDir), 0o755, true);
}

// Run ApiGen using PHAR
print "📦 Using ApiGen PHAR...\n";

// Download ApiGen PHAR if it doesn't exist
$pharPath = __DIR__ . '/../build/tools/apigen/apigen.phar';
$pharDir  = \dirname($pharPath);

if (!\is_dir($pharDir)) {
    \mkdir($pharDir, 0o755, true);
}

if (!\file_exists($pharPath)) {
    print "Downloading ApiGen PHAR...\n";
    // Use a specific version that's known to work
    $pharUrl = 'https://github.com/ApiGen/ApiGen/releases/download/v7.0.0-alpha.6/apigen.phar';

    $context = \stream_context_create([
        'http' => [
            'timeout'         => 30,
            'user_agent'      => 'Clansuite-ApiGen-Downloader/1.0',
            'follow_location' => true,
        ],
    ]);

    $pharContent = \file_get_contents($pharUrl, false, $context);

    if ($pharContent === false) {
        \fwrite(\STDERR, "ERROR: Failed to download ApiGen PHAR from {$pharUrl}\n");
        \fwrite(\STDERR, "You can manually download it from: https://github.com/ApiGen/ApiGen/releases\n");

        exit(1);
    }

    if (\file_put_contents($pharPath, $pharContent) === false) {
        \fwrite(\STDERR, "ERROR: Failed to save ApiGen PHAR to {$pharPath}\n");

        exit(1);
    }

    // Make PHAR executable
    \chmod($pharPath, 0o755);
    print "ApiGen PHAR downloaded successfully.\n";
}

// Run ApiGen using PHAR
$command = \sprintf(
    'php %s ' .
    '--output build/apigen ' .
    '--title "Clansuite Server Query -" ' .
    '--workers 1 ' .
    'src',
    \escapeshellarg($pharPath),
);

print "Generating API documentation...\n";
print "Command: {$command}\n";

\exec($command, $output, $returnCode);

if ($returnCode === 0) {
    print "✅ API documentation generated successfully in build/apigen/\n";
    print "You can open build/apigen/index.html in your browser to view the documentation.\n";

    exit(0);
}
\fwrite(\STDERR, "❌ Failed to generate API documentation. Exit code: {$returnCode}\n");

if ($output !== []) {
    \fwrite(\STDERR, "Output:\n" . \implode("\n", $output) . "\n");
}

exit(1);
