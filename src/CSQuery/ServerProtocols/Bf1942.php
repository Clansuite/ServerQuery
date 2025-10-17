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

use function array_chunk;
use function array_shift;
use function count;
use function explode;
use function trim;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Battlefield 1942 protocol implementation.
 *
 * Uses GameSpy protocol.
 */
class Bf1942 extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Battlefield 1942';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Battlefield 1942'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'bf1942';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Battlefield'];
    protected int $port_diff       = 8433;

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct($address, $queryport);
    }

    /**
     * query_server method.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        if ($this->online) {
            $this->reset();
        }

        if ($this->queryport <= 0 || $this->address === '') {
            $this->errstr = 'Query port or address not set';

            return false;
        }

        $queryPort = $this->queryport + $this->port_diff;

        // BF1942 uses GameSpy protocol
        $command = "\x5C\x73\x74\x61\x74\x75\x73\x5C";

        $result = $this->udpClient->query($this->address ?? '', $queryPort, $command);

        if ($result === null || $result === '' || $result === '0') {
            $this->errstr = 'No reply received';

            return false;
        }

        // Add to debug for capture tool
        $this->debug[] = [$command, $result];

        // Parse the GameSpy response
        // Format: \key\value\key\value...
        $this->parseResponse($result);

        $this->online = true;

        return true;
    }

    /**
     * query method.
     */
    #[Override]
    public function query(ServerAddress $addr): ServerInfo
    {
        $this->address   = $addr->ip;
        $this->queryport = $addr->port;
        $this->query_server(true, true);

        return new ServerInfo(
            address: $this->address,
            queryport: $this->queryport,
            online: $this->online,
            gamename: $this->gamename,
            gameversion: $this->gameversion,
            servertitle: $this->servertitle,
            mapname: $this->mapname,
            gametype: $this->gametype,
            numplayers: $this->numplayers,
            maxplayers: $this->maxplayers,
            rules: $this->rules,
            players: $this->players,
            errstr: $this->errstr,
        );
    }

    /**
     * getProtocolName method.
     */
    #[Override]
    public function getProtocolName(): string
    {
        return $this->protocol;
    }

    /**
     * getVersion method.
     */
    #[Override]
    public function getVersion(ServerInfo $info): string
    {
        return $info->gameversion ?? 'unknown';
    }

    /**
     * Parse the GameSpy response.
     */
    private function parseResponse(string $response): void
    {
        // Remove leading/trailing backslashes
        $response = trim($response, '\\');

        // Split by \
        $parts = explode('\\', $response);

        // Remove empty first element if present
        if (($parts[0] ?? '') === '') {
            array_shift($parts);
        }

        $this->players  = [];
        $parsingPlayers = false;

        foreach (array_chunk($parts, 2) as $chunk) {
            if (!isset($chunk[0], $chunk[1])) {
                continue;
            }

            [$key, $value] = $chunk;

            if ($key === 'playername') {
                $parsingPlayers  = true;
                $this->players[] = ['name' => $value];
            } elseif ($parsingPlayers) {
                // Additional player fields
                if ($this->players !== []) {
                    $lastPlayer       = &$this->players[count($this->players) - 1];
                    $lastPlayer[$key] = $value;
                }
            } else {
                // Server info
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
    }
}
