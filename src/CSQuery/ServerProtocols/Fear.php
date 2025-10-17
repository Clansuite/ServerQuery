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
 * Queries F.E.A.R. game servers.
 *
 * Extends the Quake 4 protocol to handle F.E.A.R. specific server queries,
 * retrieving information about server status, players, and game settings.
 * Enables monitoring of F.E.A.R. multiplayer servers.
 */
class Fear extends Quake4
{
    /**
     * Protocol name.
     */
    public string $name = 'F.E.A.R.';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['F.E.A.R.'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'fear';

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

        // F.E.A.R. uses the same packet format as Doom3/Quake4
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
        $this->gamename    = (string) ($info['gamename'] ?? 'FEAR');
        $this->gameversion = $this->translateProtocolVersion((string) ($info['protocol'] ?? $info['si_version'] ?? ''));
        $this->servertitle = (string) ($info['si_name'] ?? '');
        $this->mapname     = (string) ($info['si_map'] ?? '');
        $this->gametype    = (string) ($info['si_rules'] ?? '');
        $this->maxplayers  = (int) ($info['si_maxPlayers'] ?? 0);
        $this->numplayers  = 0; // Will be calculated from players

        // Store all rules
        $this->rules = $info;

        // Get players if requested
        if ($getPlayers && isset($info['si_players'])) {
            $this->parsePlayers((string) $info['si_players']);
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
        // For F.E.A.R., extract version like 1.08 from the string
        if (preg_match('/(\d+\.\d+)/', $protocol, $matches) !== false && isset($matches[1])) {
            return 'v' . $matches[1];
        }

        return $protocol;
    }
}
