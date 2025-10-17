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
 * Ravaged protocol implementation.
 *
 * Uses Unreal2 query protocol.
 */
class Ravaged extends Unreal2 implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Ravaged';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Ravaged'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'ravaged';

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
        return parent::query($addr);
    }
}
