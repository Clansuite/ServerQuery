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

namespace Clansuite\ServerQuery\ServerProtocols;

use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Override;

/**
 * Wolfenstein Enemy Territory protocol implementation.
 *
 * Uses Quake 3 query protocol.
 */
class Et extends Quake3Arena implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Et';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Wolfenstein Enemy Territory'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'et';

    /**
     * Constructor.
     */
    public function __construct(mixed $address = null, mixed $queryport = null)
    {
        parent::__construct($address, $queryport);
    }

    /**
     * query method.
     */
    #[Override]
    public function query(ServerAddress $addr): ServerInfo
    {
        return parent::query($addr);
    }

    /**
     * getProtocolName method.
     */
    #[Override]
    public function getProtocolName(): string
    {
        return $this->protocol;
    }

    /**
     * getVersion method.
     */
    #[Override]
    public function getVersion(ServerInfo $info): string
    {
        return $info->gameversion ?? 'unknown';
    }
}
