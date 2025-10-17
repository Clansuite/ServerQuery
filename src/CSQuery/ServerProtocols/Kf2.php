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
 * Killing Floor 2 protocol implementation.
 *
 * Extends Steam protocol with Killing Floor 2 specific port calculation.
 * Query port = host port + 19238
 */
class Kf2 extends Steam
{
    /**
     * Protocol name.
     */
    public string $name = 'Killing Floor 2';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Killing Floor 2'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'A2S';

    /**
     * Query server information.
     *
     * @return bool True on success, false on failure
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        $result = parent::query_server($getPlayers, $getRules);

        if ($result) {
            // For Killing Floor 2: query_port = host_port + 19238
            // So host_port = query_port - 19238
            $this->hostport = ($this->queryport ?? 0) - 19238;
        }

        return $result;
    }
}
