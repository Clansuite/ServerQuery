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

namespace Clansuite\Capture\Storage;

use Clansuite\Capture\CaptureResult;

interface FixtureStorageInterface
{
    public function save(string $protocol, string $version, string $ip, int $port, CaptureResult $result): string;

    public function load(string $protocol, string $version, string $ip, int $port): ?CaptureResult;

    /**
     * @return array<mixed>
     */
    public function listAll(): array;
}
