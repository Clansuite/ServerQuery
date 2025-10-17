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

namespace Clansuite\ServerQuery\Util;

use function base64_decode;
use function count;
use function file_exists;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function microtime;
use Exception;
use Override;

/**
 * Mock UDP Client for replay-based testing using captured fixtures.
 */
final class MockUdpClient extends UdpClient
{
    /**
     * @var array<array<string, mixed>>
     */
    private array $captures    = [];
    private int $responseIndex = 0;

    /**
     * Load responses from a JSON fixture file.
     */
    public function loadFixture(string $jsonFile): bool
    {
        if (!file_exists($jsonFile)) {
            return false;
        }

        $c = file_get_contents($jsonFile);

        if ($c === false) {
            throw new Exception('Could not read fixture file: ' . $jsonFile);
        }

        $metadata = json_decode($c, true);

        if (!is_array($metadata) || !isset($metadata['captures']) || !is_array($metadata['captures'])) {
            return false;
        }

        $this->captures = [];

        foreach ($metadata['captures'] as $capture) {
            if (!is_array($capture)) {
                continue;
            }

            $sent = null;

            if (isset($capture['sent']) && is_string($capture['sent'])) {
                $sent = base64_decode($capture['sent'], true);

                if ($sent === false) {
                    $sent = null;
                }
            }

            $received = null;

            if (isset($capture['received']) && is_string($capture['received'])) {
                $received = base64_decode($capture['received'], true);

                if ($received === false) {
                    $received = null;
                }
            }

            $this->captures[] = [
                'sent'      => $sent,
                'received'  => $received,
                'timestamp' => $capture['timestamp'] ?? microtime(true),
            ];
        }

        $this->responseIndex = 0;

        return $this->captures !== [];
    }

    /**
     * Mock query method that returns pre-recorded responses based on request matching.
     */
    #[Override]
    public function query(string $address, int $port, string $packet): ?string
    {
        // If we have captures with sent packets, try to match the request
        if ($this->responseIndex < count($this->captures)) {
            $capture = $this->captures[$this->responseIndex] ?? [];

            // If the capture has a sent packet, check if it matches (or is empty)
            $sentExists = isset($capture['sent']) && is_string($capture['sent']);

            if ($sentExists && ($capture['sent'] === '' || $capture['sent'] === $packet)) {
                $response = $capture['received'] ?? null;
                $this->responseIndex++;

                return is_string($response) ? $response : null;
            }
        }

        // Fallback: return responses in sequence regardless of request matching
        if ($this->responseIndex < count($this->captures)) {
            $response = $this->captures[$this->responseIndex]['received'] ?? null;
            $this->responseIndex++;

            return is_string($response) ? $response : null;
        }

        return null; // No more responses
    }

    /**
     * Reset the mock for reuse.
     */
    public function reset(): void
    {
        $this->responseIndex = 0;
    }

    /**
     * Get the number of available captures.
     */
    public function getCaptureCount(): int
    {
        return count($this->captures);
    }
}
