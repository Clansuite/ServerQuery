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

namespace Clansuite\Capture\Protocol;

use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;

interface ProtocolInterface
{
    public function query(ServerAddress $addr): ServerInfo;

    public function getProtocolName(): string;

    public function getVersion(ServerInfo $info): string;
}
