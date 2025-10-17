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
use Override;

/**
 * Implements the query protocol for Counter-Strike 2 servers.
 * Uses the Steam protocol to retrieve server information, player lists, and game statistics.
 */
class Cs2 extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'Counter-Strike 2';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Counter-Strike 2'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Counter-Strike'];

    /**
     * CS2 specific port adjustment if needed.
     * Some CS2 servers use same port for game and queries (unlike traditional Valve +1 convention).
     */
    protected int $port_diff = 0;

    /**
     * Constructor.
     */
    public function __construct(mixed $address = null, mixed $queryport = null)
    {
        $address   = $address === null ? null : (is_string($address) ? $address : null);
        $queryport = $queryport === null ? null : (is_int($queryport) ? $queryport : null);
        parent::__construct($address, $queryport);

        // For CS2, query port is game port + 1
        if ($queryport !== null) {
            $this->queryport = $queryport + $this->port_diff;
        }
    }

    /**
     * Returns a native join URI for CS2.
     */
    #[Override]
    public function getNativeJoinURI(): string
    {
        return 'steam://connect/' . $this->address . ':' . $this->hostport;
    }

    /**
     * query_server method.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        // CS2 does not respond to A2S_RULES queries, so skip rules
        return parent::query_server($getPlayers, false);
    }
}
