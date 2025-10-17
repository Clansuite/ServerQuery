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
 * GTA: Multi Theft Auto protocol implementation.
 *
 * Extends ASE protocol.
 */
class GtaMta extends Ase
{
    /**
     * Protocol name.
     */
    public string $name = 'GTA: Multi Theft Auto';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['GTA: Multi Theft Auto'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'gta-san-andreas-mta';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['GTA: San Andreas - MTA'];

    /**
     * Attempt query on given port and fallback to port+123 (ASE query port) when no reply.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        // Try as provided first
        if (parent::query_server($getPlayers, $getRules)) {
            return true;
        }

        // If no reply, try client port + 123 (ASE query offset)
        $tryPort = ($this->queryport ?? 0) + 123;

        // avoid overflow
        if ($tryPort <= 65535) {
            $this->queryport = $tryPort;

            return parent::query_server($getPlayers, $getRules);
        }

        return false;
    }
}
