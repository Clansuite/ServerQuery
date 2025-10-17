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

use function is_int;
use function is_string;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Override;

/**
 * Conan Exiles protocol implementation.
 *
 * Uses Steam query protocol.
 */
class Conan extends Steam implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Conan';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Conan Exiles'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'conan';

    /**
     * Constructor.
     */
    public function __construct(mixed $address = null, mixed $queryport = null)
    {
        $address   = $address === null ? null : (is_string($address) ? $address : null);
        $queryport = $queryport === null ? null : (is_int($queryport) ? $queryport : null);
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
