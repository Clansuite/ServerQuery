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
if (\is_file(__DIR__ . '/../build-tools/apigen/vendor/autoload.php')) {
    require __DIR__ . '/../build-tools/apigen/vendor/autoload.php';
} else {
    \fwrite(\STDERR, "ERROR: unable to find autoloader\n");

    exit(1);
}

// Create build directory if it doesn't exist
$buildDir = __DIR__ . '/../build/apigen';

if (!\is_dir(\dirname($buildDir))) {
    \mkdir(\dirname($buildDir), 0o755, true);
}

// Run ApiGen using installed binary
print "📦 Using ApiGen from build-tools/apigen...\n";

$apigenBinary = __DIR__ . '/../build-tools/apigen/vendor/bin/apigen';

// Run ApiGen
$command = \sprintf(
    '%s ' .
    '--output build/apigen ' .
    '--title "Clansuite Server Query -" ' .
    '--workers 2 ' .
    'src',
    \escapeshellarg($apigenBinary),
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
