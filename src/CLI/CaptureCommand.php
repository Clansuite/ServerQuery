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

use function error_log;
use Clansuite\Capture\CaptureService;
use Exception;

/**
 * Command-line interface for capturing game server information.
 */
class CaptureCommand
{
    public function __construct(private readonly CaptureService $captureService)
    {
    }

    /**
     * Executes the capture command with the given arguments.
     *
     * @param array<string> $argv command-line arguments
     *
     * @return int exit code (0 for success, 1 for failure)
     */
    public function run(array $argv): int
    {
        // Simple help handling: if user passed -h/--help or insufficient args, delegate to HelpCommand
        if ($this->hasHelpFlag($argv)) {
            $help = new HelpCommand;
            $help->run($argv);

            return 0;
        }

        $args     = $this->parseArgs($argv);
        $ip       = $args[0];
        $port     = $args[1];
        $protocol = $args[2];
        $options  = $args[3];

        if ($ip === '' || $port <= 0) {
            $help = new HelpCommand;
            $help->run(['', "❌ Missing required arguments: ip and port are required.\n"]);

            return 1;
        }

        try {
            $savedPath = $this->captureService->capture($ip, $port, $protocol, $options);
            print $savedPath . "\n";

            return 0;
        } catch (Exception $e) {
            error_log('❌ ' . $e->getMessage());

            return 1;
        }
    }

    /**
     * @param array<string> $argv
     */
    private function hasHelpFlag(array $argv): bool
    {
        foreach ($argv as $arg) {
            if ($arg === '-h' || $arg === '--help' || $arg === 'help') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string> $argv
     *
     * @return array{string, int, string, array<mixed>}
     */
    private function parseArgs(array $argv): array
    {
        $ip       = $argv[1] ?? '';
        $port     = (int) ($argv[2] ?? 0);
        $protocol = $argv[3] ?? 'auto';
        $options  = [];

        // Basic parsing, can be enhanced
        return [$ip, $port, $protocol, $options];
    }
}
