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
use function preg_match;
use function strlen;
use function substr;
use Override;

/**
 * Implements the query protocol for Enemy Territory: Quake Wars servers.
 * Extends the Quake 4 protocol with game-specific query handling and data parsing.
 */
class Etqw extends Quake4
{
    /**
     * Protocol name.
     */
    public string $name = 'Enemy Territory Quake Wars';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Enemy Territory Quake Wars'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'etqw';

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

        // ETQW uses the same packet format as Doom3/Quake4
        $command = "\xFF\xFFgetInfo\x00\x01\x00\x00\x00";

        if (($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $command)) === '' || ($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $command)) === '0' || ($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $command)) === false) {
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
        $this->gamename    = isset($info['gamename']) && is_string($info['gamename']) ? $info['gamename'] : 'baseETQW-1';
        $this->gameversion = $this->translateProtocolVersion(isset($info['protocol']) && is_string($info['protocol']) ? $info['protocol'] : (isset($info['si_version']) && is_string($info['si_version']) ? $info['si_version'] : ''));
        $this->servertitle = isset($info['si_name']) && is_string($info['si_name']) ? $info['si_name'] : '';
        $this->mapname     = isset($info['si_map']) && is_string($info['si_map']) ? $info['si_map'] : '';
        $this->gametype    = isset($info['si_rules']) && is_string($info['si_rules']) ? $info['si_rules'] : '';
        $this->maxplayers  = isset($info['si_maxPlayers']) && is_int($info['si_maxPlayers']) ? $info['si_maxPlayers'] : 0;
        $this->numplayers  = 0; // Will be calculated from players

        // Store all rules
        $this->rules = $info;

        // Get players if requested
        if ($getPlayers && isset($info['si_players']) && is_string($info['si_players'])) {
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
        // For ETQW, extract version like 1.5.12663.12663 from the string
        if (preg_match('/(\d+\.\d+\.\d+\.\d+)/', $protocol, $matches) !== false && isset($matches[1])) {
            return 'v' . $matches[1];
        }

        return $protocol;
    }
}
