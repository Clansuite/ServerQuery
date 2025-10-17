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
use function pack;
use function preg_match;
use function strlen;
use function substr;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * DDnet protocol implementation.
 *
 * Based on Teeworlds protocol.
 *
 * Server: https://ddnet.org/status/#server-0
 *
 * Official website: https://ddnet.org
 * Protocol: https://ddnet.org/docs/libtw2/protocol/
 * Packet: https://ddnet.org/docs/libtw2/packet/
 * Connection: https://ddnet.org/docs/libtw2/connection/
 */
class Ddnet extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'DDnet';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['DDnet'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'ddnet';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Teeworlds'];
    public string $gameport        = '8303';

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct($address, $queryport);
    }

    /**
     * getServerLink method.
     */
    public function getServerLink(): string
    {
        return 'ddnet://' . $this->address . ':' . $this->gameport;
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

        // Try extended server info first (DDnet specific)
        $command = $this->buildExtendedQueryPacket();

        if (($result = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $command)) === '' || ($result = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $command)) === '0' || ($result = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $command)) === false) {
            // Fall back to vanilla Teeworlds query
            $command = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x67\x69\x65\x33\x05";
            $result  = $this->sendCommand($this->address ?? '', $this->queryport ?? 0, $command);
        }

        if ($result === '' || $result === '0' || $result === false) {
            $this->errstr = 'No response from server';

            return false;
        }

        $this->hostport = $this->queryport ?? 0;

        // Parse the response
        return $this->parseResponse($result);
    }

    /**
     * getProtocolName method.
     */
    #[Override]
    public function getProtocolName(): string
    {
        return 'ddnet';
    }

    /**
     * getVersion method.
     */
    #[Override]
    public function getVersion(ServerInfo $info): string
    {
        return (string) ($info->rules['version'] ?? 'unknown');
    }

    /**
     * getQueryString method.
     */
    public function getQueryString(ServerAddress $address): string
    {
        return "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x67\x69\x65\x33\x05";
    }

    /**
     * parseResponseData method.
     */
    public function parseResponseData(string $data): ServerInfo
    {
        $serverInfo = new ServerInfo;

        $len = strlen($data);

        if ($len < 15) {
            return $serverInfo;
        }

        // Check for extended response
        if (substr($data, 10, 4) === 'iext') {
            return $this->parseExtendedResponseData($data);
        }

        // Check for vanilla response
        if (substr($data, 10, 5) === 'inf35') {
            return $this->parseVanillaResponseData($data);
        }

        return $serverInfo;
    }

    /**
     * query method.
     */
    #[Override]
    public function query(ServerAddress $addr): ServerInfo
    {
        // Try extended query first
        $queryString = $this->buildExtendedQueryPacket();
        $response    = $this->sendCommand($addr->ip, $addr->port, $queryString);

        if ($response === '' || $response === '0' || $response === false) {
            // Fall back to vanilla query
            $queryString = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\x67\x69\x65\x33\x05";
            $response    = $this->sendCommand($addr->ip, $addr->port, $queryString);
        }

        if ($response === '' || $response === '0' || $response === false) {
            return new ServerInfo;
        }

        return $this->parseResponseData($response);
    }

    private function buildExtendedQueryPacket(): string
    {
        // Extended server info request format:
        // Connectionless header + extended request
        $packet = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff"; // connectionless header

        $packet .= 'xe'; // magic_bytes
        $packet .= pack('n', 0); // extra_token (big-endian 16-bit)
        $packet .= pack('n', 0); // reserved
        $packet .= "\xff\xff\xff\xff"; // padding
        $packet .= 'gie3'; // vanilla_request
        $packet .= "\x00"; // token

        return $packet;
    }

    private function parseResponse(string $result): bool
    {
        $len = strlen($result);

        if ($len < 15) {
            $this->errstr = 'Invalid response (too short)';

            return false;
        }

        // Check for extended response (starts with "iext")
        if (substr($result, 10, 4) === 'iext') {
            return $this->parseExtendedResponse($result);
        }

        // Check for vanilla response (starts with "inf35")
        if (substr($result, 10, 5) === 'inf35') {
            return $this->parseVanillaResponse($result);
        }

        $this->errstr = 'Unknown response format';

        return false;
    }

    private function parseVanillaResponse(string $result): bool
    {
        $len = strlen($result);

        // Check header
        $header = substr($result, 0, 15);

        if ($header !== "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xffinf35") {
            $this->errstr = 'Invalid vanilla response header';

            return false;
        }

        $i = 15; // start after header

        // Version
        $this->rules['version'] = $this->readString($result, $i);

        // Hostname
        $this->servertitle = $this->readString($result, $i);

        // Map
        $this->mapname = $this->readString($result, $i);

        // Game description (string) and game directory (string)
        $this->rules['game_descr'] = $this->readString($result, $i);
        $this->rules['gamedir']    = $this->readString($result, $i);

        // Flags (string)
        $this->rules['flags'] = $this->readString($result, $i);

        // Player count
        $this->numplayers = (int) $this->readString($result, $i);

        // Max players
        $this->maxplayers = (int) $this->readString($result, $i);

        // Num players total
        $this->rules['num_players_total'] = (int) $this->readString($result, $i);

        // Max players total
        $this->rules['maxplayers_total'] = (int) $this->readString($result, $i);

        // Players
        $this->players = [];

        while ($i < $len) {
            $player = [];

            // Vanilla Teeworlds player format (as used by many parsers):
            // name (str), clan (str), flag/country (str), score (str), team (str)
            $player['name']    = $this->readString($result, $i);
            $player['clan']    = $this->readString($result, $i);
            $player['country'] = $this->readString($result, $i);
            $player['score']   = $this->readString($result, $i);
            $player['team']    = $this->readString($result, $i);

            $this->players[] = $player;
        }
        $this->numplayers = count($this->players);

        // Try to parse maxplayers from map name like [num/max]
        if (preg_match('/\[(\d+)\/(\d+)\]$/', $this->mapname, $matches) !== false && isset($matches[2])) {
            $this->maxplayers = (int) $matches[2];
        }

        $this->online = true;

        return true;
    }

    private function parseExtendedResponse(string $result): bool
    {
        $len = strlen($result);

        // Check header (10 padding + "iext")
        $header = substr($result, 0, 14);

        if ($header !== "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xffiext") {
            $this->errstr = 'Invalid extended response header';

            return false;
        }

        $i = 14; // start after header

        // Token (int)
        $this->readInt($result, $i);

        // Version
        $this->rules['version'] = $this->readString($result, $i);

        // Name
        $this->servertitle = $this->readString($result, $i);

        // Map
        $this->mapname = $this->readString($result, $i);

        // Map CRC (int)
        $this->rules['map_crc'] = $this->readInt($result, $i);

        // Map size (int)
        $this->rules['map_size'] = $this->readInt($result, $i);

        // Game type
        $this->rules['gametype'] = $this->readString($result, $i);

        // Flags (int)
        $this->rules['flags'] = $this->readInt($result, $i);

        // Num players (int)
        $this->numplayers = $this->readInt($result, $i);

        // Max players (int)
        $this->maxplayers = $this->readInt($result, $i);

        // Num clients (int)
        $this->rules['num_clients'] = $this->readInt($result, $i);

        // Max clients (int)
        $this->rules['max_clients'] = $this->readInt($result, $i);

        // Reserved (string, should be empty)
        $this->readString($result, $i);

        // Players
        while ($i < $len) {
            $player = [];

            $player['name']      = $this->readString($result, $i);
            $player['clan']      = $this->readString($result, $i);
            $player['country']   = $this->readInt($result, $i);
            $player['score']     = $this->readInt($result, $i);
            $player['is_player'] = $this->readInt($result, $i);
            // Reserved
            $this->readString($result, $i);

            $this->players[] = $player;
        }

        $this->online = true;

        return true;
    }

    private function readInt(string $data, int &$i): int
    {
        // According to the DDNet / Teeworlds protocol, "int" is encoded as
        // a decimal ASCII string terminated by a null byte. So read it as a
        // null-terminated string and cast to int.
        $str = $this->readString($data, $i);

        return (int) $str;
    }

    private function readString(string $data, int &$i): string
    {
        $start = $i;

        while ($i < strlen($data) && $data[$i] !== "\x00") {
            $i++;
        }
        $string = substr($data, $start, $i - $start);
        $i++; // skip null terminator

        return $string;
    }

    private function parseVanillaResponseData(string $data): ServerInfo
    {
        $serverInfo = new ServerInfo;
        $len        = strlen($data);

        $header = substr($data, 0, 15);

        if ($header !== "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xffinf35") {
            return $serverInfo;
        }

        $i = 15;

        $serverInfo->rules['version'] = $this->readString($data, $i);
        $serverInfo->servertitle      = $this->readString($data, $i);
        $serverInfo->mapname          = $this->readString($data, $i);
        // Game description and game directory
        $serverInfo->rules['game_descr']        = $this->readString($data, $i);
        $serverInfo->rules['gamedir']           = $this->readString($data, $i);
        $serverInfo->rules['flags']             = $this->readString($data, $i);
        $serverInfo->numplayers                 = (int) $this->readString($data, $i);
        $serverInfo->maxplayers                 = (int) $this->readString($data, $i);
        $serverInfo->rules['num_players_total'] = (int) $this->readString($data, $i);
        $serverInfo->rules['maxplayers_total']  = (int) $this->readString($data, $i);

        while ($i < $len) {
            $player = [];

            // Vanilla Teeworlds player format: name, clan, flag/country, score, team
            $player['name']    = $this->readString($data, $i);
            $player['clan']    = $this->readString($data, $i);
            $player['country'] = $this->readString($data, $i);
            $player['score']   = $this->readString($data, $i);
            $player['team']    = $this->readString($data, $i);
            // Vanilla responses do not include is_player flag; assume true
            $player['is_player'] = 1;

            $serverInfo->players[] = $player;
        }

        $serverInfo->online = true;

        return $serverInfo;
    }

    private function parseExtendedResponseData(string $data): ServerInfo
    {
        $serverInfo = new ServerInfo;
        $len        = strlen($data);

        $header = substr($data, 0, 14);

        if ($header !== "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xffiext") {
            return $serverInfo;
        }

        $i = 14;

        $this->readInt($data, $i);
        $serverInfo->rules['version']     = $this->readString($data, $i);
        $serverInfo->servertitle          = $this->readString($data, $i);
        $serverInfo->mapname              = $this->readString($data, $i);
        $serverInfo->rules['map_crc']     = $this->readInt($data, $i);
        $serverInfo->rules['map_size']    = $this->readInt($data, $i);
        $serverInfo->rules['gametype']    = $this->readString($data, $i);
        $serverInfo->rules['flags']       = $this->readInt($data, $i);
        $serverInfo->numplayers           = $this->readInt($data, $i);
        $serverInfo->maxplayers           = $this->readInt($data, $i);
        $serverInfo->rules['num_clients'] = $this->readInt($data, $i);
        $serverInfo->rules['max_clients'] = $this->readInt($data, $i);
        $this->readString($data, $i); // reserved

        while ($i < $len) {
            $player              = [];
            $player['name']      = $this->readString($data, $i);
            $player['clan']      = $this->readString($data, $i);
            $player['country']   = $this->readInt($data, $i);
            $player['score']     = $this->readInt($data, $i);
            $player['is_player'] = $this->readInt($data, $i);
            $this->readString($data, $i); // reserved

            $serverInfo->players[] = $player;
        }

        $serverInfo->online = true;

        return $serverInfo;
    }
}
