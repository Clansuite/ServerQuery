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
use function trim;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Starbound protocol implementation.
 *
 * Custom UDP protocol.
 */
class Starbound extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Starbound';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Starbound'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'starbound';

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct();
        $this->address   = $address;
        $this->queryport = $queryport;
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

        // Starbound query packet
        $command = "Starbound\x00query\x00";

        $address = (string) $this->address;
        $port    = (int) $this->queryport;

        $result = $this->sendCommand($address, $port, $command);

        if ($result === '' || $result === '0' || $result === false) {
            $this->errstr = 'No reply received';

            return false;
        }

        // Parse the response
        // The response is a string like "Starbound <version> <servername> <players>/<maxplayers> <map>"
        $data  = trim($result);
        $parts = explode(' ', $data);

        if (count($parts) < 5) {
            $this->errstr = 'Invalid response format';

            return false;
        }

        $this->gamename    = 'Starbound';
        $this->gameversion = $parts[1] ?? '';
        $this->servertitle = $parts[2] ?? '';
        $this->mapname     = $parts[4] ?? '';

        $playerPart  = $parts[3] ?? '';
        $playerParts = explode('/', $playerPart);

        if (count($playerParts) === 2) {
            $this->numplayers = (int) $playerParts[0];
            $this->maxplayers = (int) $playerParts[1];
        }

        // No player list or rules in this simple protocol
        $this->players = [];
        $this->rules   = [];

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
}
