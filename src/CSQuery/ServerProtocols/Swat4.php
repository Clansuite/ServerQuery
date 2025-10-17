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
 * SWAT 4 protocol implementation.
 *
 * Uses Gamespy2 query protocol.
 */
class Swat4 extends Gamespy2 implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Swat4';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['SWAT 4'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'swat4';

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct($address, $queryport);
    }

    /**
     * query method.
     */
    #[Override]
    public function query(ServerAddress $addr): ServerInfo
    {
        // For SWAT 4, query port is game port + 1
        $queryAddr = new ServerAddress($addr->ip, $addr->port + 1);

        return parent::query($queryAddr);
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
