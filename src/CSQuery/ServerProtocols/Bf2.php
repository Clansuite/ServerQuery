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

use function count;
use function explode;
use function is_numeric;
use function preg_split;
use function strlen;
use function substr;
use Override;

/**
 * Battlefield 2 protocol implementation.
 *
 * Based on GameSpy 3 protocol.
 */
class Bf2 extends Gamespy3
{
    /**
     * Protocol name.
     */
    public string $name = 'Battlefield 2';

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Bf2';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Battlefield'];

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Battlefield 2'];
    protected int $port_diff     = 13333;

    /**
     * query_server method.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        if ($this->online) {
            $this->reset();
        }

        $queryPort = ($this->queryport ?? 0) + $this->port_diff;

        // BF2 uses GameSpy 3 protocol with specific packet (no challenge needed)
        $packet = "\xFE\xFD\x00\x10\x20\x30\x40\xFF\xFF\xFF\x01";

        $result = $this->udpClient->query($this->address ?? '', $queryPort, $packet);

        if ($result === null || $result === '' || $result === '0') {
            $this->errstr = 'No response from server';

            return false;
        }

        // Add to debug for capture tool
        $this->debug[] = [$packet, $result];

        // Parse the response using the inherited method
        $this->processResponse($result);

        $this->online = true;

        return true;
    }

    /**
     * Parse the GameSpy 3 response.
     */
    protected function processResponse(string $response): void
    {
        // Skip header: packet type (1), session id (4), splitnum\0 (9)
        $offset = 1 + 4 + 9;

        if (strlen($response) < $offset) {
            $this->errstr = 'Response too short';

            return;
        }
        $offset++;

        // Skip next byte
        $offset++;

        // The rest is the data
        $data = substr($response, $offset);

        // Split into server info and players/teams
        $split = preg_split('/\\x00\\x00\\x01/', $data, 2);

        if ($split === [] || $split === false) {
            $this->errstr = 'Failed to split response';

            return;
        }

        // Parse server details
        $this->parseDetails($split[0]);

        // Parse players and teams if present
        if (isset($split[1])) {
            $this->parsePlayersAndTeams($split[1]);
        }
    }

    /**
     * Parse server details.
     */
    private function parseDetails(string $data): void
    {
        $parts = explode("\x00", $data);

        if (count($parts) < 2) {
            return;
        }

        $partsCount = count($parts);

        for ($i = 0; $i + 1 < $partsCount; $i += 2) {
            if (!isset($parts[$i]) || !isset($parts[$i + 1])) {
                continue;
            }

            $key   = $parts[$i];
            $value = $parts[$i + 1];

            switch ($key) {
                case 'hostname':
                    $this->servertitle = $value;

                    break;

                case 'mapname':
                    $this->mapname = $value;

                    break;

                case 'gametype':
                    $this->gametype = $value;

                    break;

                case 'maxplayers':
                    $this->maxplayers = (int) $value;

                    break;

                case 'numplayers':
                    $this->numplayers = (int) $value;

                    break;

                case 'password':
                    $this->password = $value === '1' ? 1 : 0;

                    break;

                default:
                    $this->rules[$key] = $value;

                    break;
            }
        }
    }

    /**
     * Parse players and teams.
     */
    private function parsePlayersAndTeams(string $data): void
    {
        $parts = explode("\x00", $data);

        $this->players = [];
        $i             = 0;
        $partsCount    = count($parts);

        // Skip 'player_' and empty
        if (isset($parts[$i]) && $parts[$i] === 'player_') {
            $i++;
        }

        if (isset($parts[$i]) && $parts[$i] === '') {
            $i++;
        }

        // Parse name\team pairs
        while ($i + 1 < $partsCount) {
            $name = $parts[$i++] ?? '';
            $team = $parts[$i++] ?? '';

            if (!is_numeric($name) && $name !== '') {
                $this->players[] = ['name' => $name, 'team' => $team];
            } else {
                break;
            }
        }
    }
}
