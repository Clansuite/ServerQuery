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

use function array_values;
use function count;
use function explode;
use function is_int;
use function is_numeric;
use function is_scalar;
use function is_string;
use function max;
use function preg_match;
use function substr;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * GameSpy protocol implementation (version 1).
 *
 * Used by older games like Unreal Tournament.
 */
class Gamespy extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Gamespy';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Gamespy'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Gamespy';

    /**
     * Constructor.
     */
    public function __construct(mixed $address = null, mixed $queryport = null)
    {
        parent::__construct(is_string($address) ? $address : null, is_int($queryport) ? $queryport : null);
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
        $address   = $this->address ?? '';
        $queryport = $this->queryport ?? 0;

        // Send status query
        $command = "\x5C\x73\x74\x61\x74\x75\x73\x5C";

        if (($result = $this->sendCommand($address, $queryport, $command)) === '' || ($result = $this->sendCommand($address, $queryport, $command)) === '0' || ($result = $this->sendCommand($address, $queryport, $command)) === false) {
            return false;
        }

        $this->online = true;

        // Process status response
        $this->processStatus($result);

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
     * _processStatus method.
     */
    protected function processStatus(string $buffer): void
    {
        // Skip the first \ if present
        if ($buffer !== '' && $buffer[0] === '\\') {
            $buffer = substr($buffer, 1);
        }

        // Explode on \
        $data = explode('\\', $buffer);

        $itemCount = count($data);

        // Process key-value pairs
        $this->rules   = [];
        $this->players = [];
        $numPlayers    = 0;

        $x = 0;

        while ($x + 1 < $itemCount) {
            $key = $data[$x] ?? '';
            $val = $data[$x + 1] ?? '';
            $x += 2;

            // Check for player data
            if (preg_match('/^player_(\d+)$/', $key, $matches) === 1) {
                $playerIndex = (int) $matches[1];

                if (!isset($this->players[$playerIndex])) {
                    $this->players[$playerIndex] = [];
                }
                $this->players[$playerIndex]['name'] = $val;
                $numPlayers                          = max($numPlayers, $playerIndex + 1);
            } elseif (preg_match('/^frags_(\d+)$/', $key, $matches) === 1) {
                $playerIndex = (int) $matches[1];

                if (!isset($this->players[$playerIndex])) {
                    $this->players[$playerIndex] = [];
                }
                $this->players[$playerIndex]['score'] = is_numeric($val) ? (int) $val : 0;
            } elseif (preg_match('/^ping_(\d+)$/', $key, $matches) === 1) {
                $playerIndex = (int) $matches[1];

                if (!isset($this->players[$playerIndex])) {
                    $this->players[$playerIndex] = [];
                }
                $this->players[$playerIndex]['ping'] = is_numeric($val) ? (int) $val : 0;
            } elseif ($key === 'hostname') {
                $this->servertitle = $val;
            } elseif ($key === 'mapname') {
                $this->mapname = $val;
            } elseif ($key === 'gametype') {
                $this->gametype = $val;
            } elseif ($key === 'numplayers') {
                $this->numplayers = is_numeric($val) ? (int) $val : 0;
            } elseif ($key === 'maxplayers') {
                $this->maxplayers = is_numeric($val) ? (int) $val : 0;
            } elseif ($key === 'gamever') {
                $this->gameversion = $val;
            } else {
                $this->rules[$key] = $val;
            }
        }

        // Reindex players
        $this->players = array_values($this->players);
    }
}
