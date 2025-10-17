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

use function preg_match;
use function strlen;
use function substr;
use Override;

/**
 * Implements the query protocol for Doom 3 servers.
 * Extends the Quake 4 protocol with Doom 3 specific query operations and data handling.
 */
class Doom3 extends Quake4
{
    /**
     * Protocol name.
     */
    public string $name = 'Doom 3';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Doom 3'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'doom3';

    /**
     * getProtocolName method.
     */
    #[Override]
    public function getProtocolName(): string
    {
        return $this->protocol;
    }

    /**
     * query_server method.
     */
    #[Override]
    public function query_server(mixed $getPlayers = true, mixed $getRules = true): bool
    {
        if ($this->online) {
            $this->reset();
        }

        // Doom3/Quake4 protocol uses different packet format
        $command = "\xFF\xFFgetInfo\x00\x01\x00\x00\x00";

        if (($result = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $command)) === '' || ($result = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $command)) === '0' || ($result = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $command)) === false) {
            $this->errstr = 'No reply received';

            return false;
        }

        // Parse the Doom3 packet format
        if (strlen($result) < 16) {
            $this->errstr = 'Invalid packet received';

            return false;
        }

        // Skip the packet header (16 bytes: short check, 12 bytes check2, int challengeId, int protocol, char pack)
        $data = substr($result, 16);

        // Parse server info
        $info = $this->parseDoom3Info($data);

        // Extract basic server information
        $this->gamename    = $info['gamename'] ?? 'doom3';
        $this->gameversion = $this->translateProtocolVersion($info['protocol'] ?? $info['si_version'] ?? '');
        $this->servertitle = $info['si_name'] ?? '';
        $this->mapname     = $info['si_map'] ?? '';
        $this->gametype    = $info['si_gameType'] ?? '';
        $this->maxplayers  = (int) ($info['si_maxplayers'] ?? 0);
        $this->numplayers  = 0; // Will be calculated from players

        // Store all rules
        $this->rules = $info;

        // Get players if requested
        if ($getPlayers && isset($info['si_players'])) {
            $this->parsePlayers($info['si_players']);
        }

        $this->online = true;

        return true;
    }

    /**
     * translateProtocolVersion method.
     */
    #[Override]
    protected function translateProtocolVersion(string $protocol): string
    {
        // For Doom 3, extract version like 1.3.1.1304 from the string
        if (preg_match('/(\d+\.\d+\.\d+\.\d+)/', $protocol, $matches) !== false && isset($matches[1])) {
            return 'v' . $matches[1];
        }

        return $protocol;
    }
}
