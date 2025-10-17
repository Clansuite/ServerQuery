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

use Override;

/**
 * Implements the query protocol for Ark: Survival Evolved servers.
 * Utilizes the Steam query protocol to retrieve server information, player lists, and game statistics.
 */
class ArkSurvivalEvolved extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'Ark: Survival Evolved';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Ark: Survival Evolved'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Ark: Survival Evolved'];
    protected int $port_diff       = 19238;

    /**
     * Constructor for Ark: Survival Evolved.
     *
     * @param string $address                Server address
     * @param int    $port                   Port number (game port if autoCalculateQueryPort is true, query port if false)
     * @param bool   $autoCalculateQueryPort Whether to auto-calculate query port (default: true)
     */
    public function __construct(string $address, int $port, protected bool $autoCalculateQueryPort = true)
    {
        parent::__construct($address, $port);
    }

    /**
     * Returns a native join URI for Ark SE.
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
        if ($this->autoCalculateQueryPort) {
            // For Ark SE, the query port is typically game_port + 19238
            // Store original queryport and calculate the correct one
            $originalQueryPort = $this->queryport;
            $this->queryport   = ($this->queryport ?? 0) + $this->port_diff;

            $result = parent::query_server($getPlayers, $getRules);

            // Restore original queryport
            $this->queryport = $originalQueryPort;

            return $result;
        }

        // Use the provided port directly as query port
        return parent::query_server($getPlayers, $getRules);
    }
}
