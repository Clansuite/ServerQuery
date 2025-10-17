<?php

declare(strict_types=1);

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

use function array_key_exists;
use function max;
use function ord;
use function strlen;
use function strpos;
use function substr;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * All-Seeing Eye protocol implementation.
 *
 * Used for Multi Theft Auto and other ASE-based games.
 */
class Ase extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'All-Seeing Eye';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Multi Theft Auto'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'ASE';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['ASE'];

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct();

        if ($address !== null) {
            $this->address = $address;
        }

        if ($queryport !== null) {
            $this->queryport = $queryport;
        }
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

        $command = 's';

        $address = $this->address ?? '';
        $port    = $this->queryport ?? 0;

        if (($result = $this->sendCommand($address, $port, $command)) === '' || ($result = $this->sendCommand($address, $port, $command)) === '0' || ($result = $this->sendCommand($address, $port, $command)) === false) {
            $this->errstr = 'No reply received';

            return false;
        }

        // Check for valid response
        if (strlen($result) < 4) {
            $this->errstr = 'Response too short';

            return false;
        }

        // Read the header
        $header = substr($result, 0, 4);

        if ($header !== 'EYE1') {
            $this->errstr = 'Invalid header';

            return false;
        }

        $buffer = substr($result, 4);

        // Read fixed header fields
        $this->rules = [];
        $gamename    = $this->readLengthPrefixedString($buffer);
        $port        = $this->readLengthPrefixedString($buffer);
        $servername  = $this->readLengthPrefixedString($buffer);
        $gametype    = $this->readLengthPrefixedString($buffer);
        $map         = $this->readLengthPrefixedString($buffer);
        $version     = $this->readLengthPrefixedString($buffer);
        $password    = $this->readLengthPrefixedString($buffer);
        $num_players = $this->readLengthPrefixedString($buffer);
        $max_players = $this->readLengthPrefixedString($buffer);

        // Populate initial rule set from those fields so callers can access them via rules
        if ($gamename !== '') {
            $this->rules['gamename'] = $gamename;
        }

        if ($port !== '') {
            $this->rules['port'] = $port;
        }

        if ($servername !== '') {
            $this->rules['hostname'] = $servername;
        }

        if ($gametype !== '') {
            $this->rules['gametype'] = $gametype;
        }

        if ($map !== '') {
            $this->rules['map'] = $map;
        }

        if ($version !== '') {
            $this->rules['version'] = $version;
        }

        if ($password !== '') {
            $this->rules['password'] = $password;
        }

        if ($num_players !== '') {
            $this->rules['num_players'] = $num_players;
        }

        if ($max_players !== '') {
            $this->rules['max_players'] = $max_players;
        }

        // Default dedicated flag
        if (!array_key_exists('dedicated', $this->rules)) {
            $this->rules['dedicated'] = 1;
        }

        // Parse remaining key/value pairs
        while ($buffer !== '') {
            $key = $this->readLengthPrefixedString($buffer);

            if ($key === '' || $key === '0') {
                break;
            }
            $value             = $this->readLengthPrefixedString($buffer);
            $this->rules[$key] = $value;
        }

        // Parse players
        $this->players = [];

        while ($buffer !== '') {
            $flags  = ord($buffer[0]);
            $buffer = substr($buffer, 1);

            $player = [];

            if (($flags & 1) !== 0) {
                $player['name'] = $this->readLengthPrefixedString($buffer);
            }

            if (($flags & 2) !== 0) {
                $player['team'] = $this->readLengthPrefixedString($buffer);
            }

            if (($flags & 4) !== 0) {
                $player['skin'] = $this->readLengthPrefixedString($buffer);
            }

            if (($flags & 8) !== 0) {
                $player['score'] = $this->readLengthPrefixedString($buffer);
            }

            if (($flags & 16) !== 0) {
                $player['ping'] = $this->readLengthPrefixedString($buffer);
            }

            if (($flags & 32) !== 0) {
                $player['time'] = $this->readLengthPrefixedString($buffer);
            }

            if ($player !== []) {
                $this->players[] = $player;
            }
        }

        // Set info from rules
        $this->gamename    = (string) ($this->rules['gamename'] ?? '');
        $this->gametype    = (string) ($this->rules['gametype'] ?? '');
        $this->mapname     = (string) ($this->rules['map'] ?? '');
        $this->gameversion = (string) ($this->rules['version'] ?? '');
        $this->servertitle = (string) ($this->rules['hostname'] ?? '');
        $this->numplayers  = (int) ($this->rules['num_players'] ?? 0);
        $this->maxplayers  = (int) ($this->rules['max_players'] ?? 0);
        $this->password    = (int) ($this->rules['password'] ?? 0);
        $this->hostport    = (int) ($this->rules['port'] ?? 0);

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

        $this->query_server();

        $info              = new ServerInfo;
        $info->address     = $addr->ip;
        $info->queryport   = $addr->port;
        $info->online      = $this->online;
        $info->gamename    = $this->gamename;
        $info->gameversion = $this->gameversion;
        $info->servertitle = $this->servertitle;
        $info->mapname     = $this->mapname;
        $info->gametype    = $this->gametype;
        $info->numplayers  = $this->numplayers;
        $info->maxplayers  = $this->maxplayers;
        $info->players     = $this->players;
        $info->rules       = $this->rules;
        $info->errstr      = $this->errstr;

        return $info;
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

    private function readLengthPrefixedString(string &$buffer): string
    {
        // If buffer is empty, return empty string
        if (!isset($buffer[0])) {
            return '';
        }

        $length = ord($buffer[0]);
        // consume length byte
        $buffer = substr($buffer, 1);

        // the strings include an extra trailing byte; callers expect length-1 content
        $readLen = max($length - 1, 0);

        // if declared read length is larger than remaining buffer, clamp it
        $remaining = strlen($buffer);

        if ($readLen > $remaining) {
            $readLen = $remaining;
        }

        $string  = substr($buffer, 0, $readLen);
        $nullPos = strpos($string, "\0");

        if ($nullPos !== false) {
            $string = substr($string, 0, $nullPos);
        }

        // advance buffer to the next length-prefixed string
        $buffer = substr($buffer, $readLen);

        return $string;
    }
}
