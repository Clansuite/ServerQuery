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

/**
 * ARMA: Reforger protocol implementation.
 *
 * Based on Source engine protocol with ARMA Reforger specific rules parsing.
 *
 * @see https://community.bistudio.com/wiki/Arma_Reforger:Server_Config
 */
class ArmaReforger extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'ARMA: Reforger';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['ARMA: Reforger'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * ARMA: Reforger uses query port = game port + 1.
     */
    protected int $port_diff = 1;

    /**
     * @inheritDoc
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct($address, $queryport);
    }
}
