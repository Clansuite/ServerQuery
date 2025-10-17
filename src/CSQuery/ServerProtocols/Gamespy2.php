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
use function ord;
use function strlen;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * GameSpy2 protocol implementation.
 */
class Gamespy2 extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Gamespy2';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Gamespy2', 'Halo'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Gamespy2';

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

        // Send details query
        $command = "\xFE\xFD\x00\x43\x4F\x52\x59\xFF\x00\x00";

        if (($result = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $command)) === '' || ($result = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $command)) === '0' || ($result = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $command)) === false) {
            return false;
        }

        $this->online = true;

        // Process details response
        $this->processDetails($result);
        // Send players query
        $command = "\xFE\xFD\x00\x43\x4F\x52\x58\x00\xFF\xFF";

        if (($result = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $command)) !== false) {
            $this->processPlayers($result);
        }

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

    private function processDetails(string $buffer): void
    {
        $i   = 5; // Skip header
        $len = strlen($buffer);

        while ($i < $len) {
            $key = '';

            while ($i < $len && ord($buffer[$i]) !== 0) {
                $key .= $buffer[$i];
                $i++;
            }
            $i++; // Skip null

            if ($key === '' || $key === '0') {
                break;
            }

            $value = '';

            while ($i < $len && ord($buffer[$i]) !== 0) {
                $value .= $buffer[$i];
                $i++;
            }
            $i++; // Skip null

            $this->setDetail($key, $value);
        }
    }

    private function setDetail(string $key, string $value): void
    {
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

            case 'gamever':
                $this->gameversion = $value;

                break;

            default:
                $this->rules[$key] = $value;

                break;
        }
    }

    private function processPlayers(string $buffer): void
    {
        $i   = 6; // Skip header and count byte
        $len = strlen($buffer);

        // Skip player count
        if ($i < $len) {
            $i++;
        }

        // Read variable names
        $varNames = [];

        while ($i < $len) {
            $var = '';

            while ($i < $len && ord($buffer[$i]) !== 0) {
                $var .= $buffer[$i];
                $i++;
            }
            $i++; // Skip null

            if ($var === '' || $var === '0') {
                break;
            }

            $varNames[] = $var;
        }

        // Read player data
        $this->players = [];

        while ($i < $len - 4) {
            $player = [];

            foreach ($varNames as $varName) {
                $value = '';

                while ($i < $len && ord($buffer[$i]) !== 0) {
                    $value .= $buffer[$i];
                    $i++;
                }
                $i++; // Skip null

                $player[$varName] = $value;
            }

            if ($player !== []) {
                $this->players[] = $player;
            }

            if ($i >= $len || ord($buffer[$i]) === 0) {
                break;
            }
        }
    }
}
