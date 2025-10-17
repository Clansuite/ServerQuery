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

namespace Clansuite\Capture\CLI;

use function in_array;

/**
 * Displays help information for the Clansuite Server Query capture tool CLI.
 */
class HelpCommand
{
    /**
     * Executes the help command, printing usage information to stdout.
     *
     * @param array<string> $argv Command line arguments
     *
     * @return int Exit code (0 for success)
     */
    public function run(array $argv): int
    {
        // If the caller passed a known help flag as the second arg (e.g. '--help'),
        // don't treat that as a message prefix to print. Only print a prefix
        // when it's an actual message (like an error notice).
        $maybe     = $argv[1] ?? '';
        $helpFlags = ['-h', '--help', 'help', null, ''];

        $prefix = in_array($maybe, $helpFlags, true) ? '' : $maybe;

        $this->printHelp($prefix);

        return 0;
    }

    /**
     * Prints the help text to stdout with an optional prefix.
     *
     * @param string $prefix Optional prefix to prepend to the help text
     */
    public function printHelp(string $prefix = ''): void
    {
        if ($prefix !== '') {
            print $prefix;
        }

        print "Clansuite Server Query - Capture Tool CLI\n";
        print "Copyright (c) 2003-2025 Jens A. Koch.\n";
        print "License: MIT.\n";
        print "\n";
        print "Description:\n";
        print "  A command-line tool to query and capture information from supported game servers.\n";
        print "\n";
        print "Usage:\n";
        print "  capture <ip> <query_port> [protocol]    # Captures a fixture from server and stores it.\n";
        print "  capture list                            # Lists available fixtures.\n";
        print "  capture help|-h|--help                  # Shows this message.\n";
        print "\n";
        print "Arguments:\n";
        print "  <ip>         IP address or hostname of the game server\n";
        print "  <port>       Port number of the game server\n\n";
        print "\n";
        print "Examples:\n";
        print "  capture ger10.ddnet.org 8300 ddnet\n";
        print "  capture 123.123.123.123 8303 arma3\n";
        print "  capture list\n";
        print "  capture list ddnet | jq '.'    # filter and pretty-print ddnet captures\n";
        print "\n";
        print "Notes:\n";
        print "  - Use -h or --help to show this message.\n";
    }
}
