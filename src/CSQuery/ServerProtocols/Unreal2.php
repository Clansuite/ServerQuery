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

use function is_array;
use function ord;
use function substr;
use function unpack;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Unreal 2 Protocol implementation.
 *
 * Base protocol for Unreal Engine 2 games like Killing Floor.
 */
class Unreal2 extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Unreal2';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Unreal2', 'KillingFloor'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Unreal2';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Unreal Tournament'];
    public ?int $serverID          = null;

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

        $address = (string) $this->address;
        $port    = (int) $this->queryport;

        // Send details query
        $command = "\x79\x00\x00\x00\x00"; // Details packet

        if (($result = $this->sendCommand($address, $port, $command)) === '' || ($result = $this->sendCommand($address, $port, $command)) === '0' || ($result = $this->sendCommand($address, $port, $command)) === false) {
            return false;
        }

        $this->processDetails($result);
        // Send rules query if requested
        $command = "\x79\x00\x00\x00\x01";

        // Rules packet
        if (($result = $this->sendCommand($address, $port, $command)) !== false) {
            $this->processRules($result);
        }
        // Send players query if requested
        $command = "\x79\x00\x00\x00\x02";

        // Players packet
        if (($result = $this->sendCommand($address, $port, $command)) !== false) {
            $this->processPlayers($result);
        }

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
     * _processDetails method.
     */
    protected function processDetails(string $data): void
    {
        // Skip header (5 bytes)
        $data = substr($data, 5);

        // Server ID (4 bytes)
        $tmp = @unpack('V', substr($data, 0, 4));

        if (!is_array($tmp) || !isset($tmp[1])) {
            // leave serverID null on malformed response
            $this->serverID = null;
        } else {
            $this->serverID = $tmp[1];
        }
        $data = substr($data, 4);

        // Server IP (pascal string, skip)
        $len  = ord($data[0] ?? "\0");
        $data = substr($data, 1 + $len);

        // Game port (4 bytes)
        $tmp = @unpack('V', substr($data, 0, 4));

        if (!is_array($tmp) || !isset($tmp[1])) {
            // malformed, default to 0
            $this->hostport = 0;
        } else {
            $this->hostport = (int) $tmp[1];
        }
        $data = substr($data, 4);

        // Query port (4 bytes, skip)
        $data = substr($data, 4);

        // Server name (pascal string)
        $len               = ord($data[0] ?? "\0");
        $this->servertitle = substr($data, 1, $len);
        $data              = substr($data, 1 + $len);

        // Map name (pascal string)
        $len           = ord($data[0] ?? "\0");
        $this->mapname = substr($data, 1, $len);
        $data          = substr($data, 1 + $len);

        // Game type (pascal string)
        $len            = ord($data[0] ?? "\0");
        $this->gametype = substr($data, 1, $len);
        $data           = substr($data, 1 + $len);

        // Num players (4 bytes)
        $tmp = @unpack('V', substr($data, 0, 4));

        if (!is_array($tmp) || !isset($tmp[1])) {
            $this->numplayers = 0;
        } else {
            $this->numplayers = (int) $tmp[1];
        }
        $data = substr($data, 4);

        // Max players (4 bytes)
        $tmp = @unpack('V', substr($data, 0, 4));

        if (!is_array($tmp) || !isset($tmp[1])) {
            $this->maxplayers = 0;
        } else {
            $this->maxplayers = (int) $tmp[1];
        }

        // Ping (4 bytes, skip)
        // $data = substr($data, 4);
    }

    /**
     * _processRules method.
     */
    protected function processRules(string $data): void
    {
        // Skip header (5 bytes)
        $data = substr($data, 5);

        while ($data !== '') {
            // Key (pascal string)
            $len  = ord($data[0] ?? "\0");
            $key  = substr($data, 1, $len);
            $data = substr($data, 1 + $len);

            // Value (pascal string)
            $len   = ord($data[0] ?? "\0");
            $value = substr($data, 1, $len);
            $data  = substr($data, 1 + $len);

            $this->rules[$key] = $value;
        }
    }

    /**
     * _processPlayers method.
     */
    protected function processPlayers(string $data): void
    {
        // Skip header (5 bytes)
        $data = substr($data, 5);

        $this->players = [];

        while ($data !== '') {
            // Player ID (4 bytes)
            $tmp = @unpack('V', substr($data, 0, 4));

            if (!is_array($tmp) || !isset($tmp[1])) {
                // malformed, stop parsing players
                break;
            }
            $id   = (int) $tmp[1];
            $data = substr($data, 4);

            if ($id === 0) {
                break; // End of players
            }

            // Player name (pascal string)
            $len  = ord($data[0] ?? "\0");
            $name = substr($data, 1, $len);
            $data = substr($data, 1 + $len);

            // Ping (4 bytes)
            $tmp  = @unpack('V', substr($data, 0, 4));
            $ping = (is_array($tmp) && isset($tmp[1])) ? (int) $tmp[1] : 0;
            $data = substr($data, 4);

            // Score (4 bytes)
            $tmp   = @unpack('V', substr($data, 0, 4));
            $score = (is_array($tmp) && isset($tmp[1])) ? (int) $tmp[1] : 0;
            $data  = substr($data, 4);

            // Skip 4 unknown bytes
            $data = substr($data, 4);

            $this->players[] = [
                'name'  => $name,
                'ping'  => $ping,
                'score' => $score,
            ];
        }
    }
}
