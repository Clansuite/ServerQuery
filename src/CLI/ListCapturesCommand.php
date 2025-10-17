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

use const JSON_PRETTY_PRINT;
use function is_array;
use function is_string;
use function json_encode;
use function strtolower;
use Clansuite\Capture\Storage\FixtureStorageInterface;

/**
 * Lists captured game server data stored in fixtures, with optional protocol filtering.
 */
class ListCapturesCommand
{
    /**
     * Initializes the command with a fixture storage interface.
     *
     * @param FixtureStorageInterface $storage Storage interface for accessing captured data
     */
    public function __construct(private readonly FixtureStorageInterface $storage)
    {
    }

    /**
     * Executes the list captures command, outputting stored capture data in JSON format.
     *
     * @param array<string> $argv Command line arguments, optionally including a protocol filter
     *
     * @return int Exit code (0 for success)
     */
    public function run(array $argv): int
    {
        // Determine protocol filter robustly:
        // - If called as `capture list ddnet` argv will be [script, 'list', 'ddnet']
        // - If called directly as `list ddnet` argv may be [script, 'ddnet']
        $protocolFilter = null;

        if (isset($argv[1]) && strtolower($argv[1]) === 'list') {
            $protocolFilter = isset($argv[2]) && $argv[2] !== '' ? strtolower($argv[2]) : null;
        } else {
            $protocolFilter = isset($argv[1]) && $argv[1] !== '' ? strtolower($argv[1]) : null;
        }

        $captures = $this->storage->listAll();

        foreach ($captures as $capture) {
            if (!is_array($capture)) {
                continue;
            }

            if ($protocolFilter !== null) {
                $metadata = $capture['metadata'] ?? [];

                if (!is_array($metadata)) {
                    continue;
                }
                $proto = $metadata['protocol'] ?? '';

                if (!is_string($proto)) {
                    $proto = '';
                }
                $metaProto = strtolower($proto);

                if ($metaProto !== $protocolFilter) {
                    continue;
                }
            }

            print json_encode($capture, JSON_PRETTY_PRINT) . "\n";
        }

        return 0;
    }
}
