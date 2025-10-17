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
use function is_array;
use function min;
use function ord;
use function pack;
use function strlen;
use function substr;
use function unpack;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Torque Game Engine protocol implementation.
 *
 * Based on Torque Game Engine server query protocol from serverQuery.cc
 * Used by Tribes 2, Blockland, Age of Time, and other Torque-based games.
 */
class Torque extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Torque';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Torque'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Torque';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Torque'];

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

        // Send ping request first
        $pingPacket = $this->createPingPacket();

        if (($pingResponse = $this->sendCommand($address, $port, $pingPacket)) === '' || ($pingResponse = $this->sendCommand($address, $port, $pingPacket)) === '0' || ($pingResponse = $this->sendCommand($address, $port, $pingPacket)) === false) {
            return false;
        }

        // Parse ping response to get server info
        if (!$this->processPingResponse($pingResponse)) {
            return false;
        }

        // Send info request
        $infoPacket = $this->createInfoPacket();

        if (($infoResponse = $this->sendCommand($address, $port, $infoPacket)) === '' || ($infoResponse = $this->sendCommand($address, $port, $infoPacket)) === '0' || ($infoResponse = $this->sendCommand($address, $port, $infoPacket)) === false) {
            return false;
        }

        // Parse info response
        if (!$this->processInfoResponse($infoResponse)) {
            return false;
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

    private function createPingPacket(): string
    {
        // Based on Torque protocol: GamePingRequest packet
        // Format: packet_type(1) + flags(1) + key(4)
        $packetType = "\x02"; // GamePingRequest
        $flags      = "\x00";      // No special flags
        $key        = pack('N', 0); // Key (will be set by session)

        return $packetType . $flags . $key;
    }

    private function createInfoPacket(): string
    {
        // Based on Torque protocol: GameInfoRequest packet
        // Format: packet_type(1) + flags(1) + key(4)
        $packetType = "\x04"; // GameInfoRequest
        $flags      = "\x00";      // No special flags
        $key        = pack('N', 0); // Key (will be set by session)

        return $packetType . $flags . $key;
    }

    private function processPingResponse(string $buffer): bool
    {
        if (strlen($buffer) < 14) {
            return false;
        }

        // Parse ping response based on Torque protocol
        // Format: packet_type(1) + flags(1) + key(4) + version_string + protocol_version(4) + min_protocol(4) + build_version(4) + server_name(24)

        $offset = 6; // Skip packet_type, flags, key

        // Read version string (null terminated)
        $versionString = '';

        while ($offset < strlen($buffer) && ord($buffer[$offset]) !== 0) {
            $versionString .= $buffer[$offset];
            $offset++;
        }
        $offset++; // Skip null

        $this->gameversion = $versionString;

        // Read protocol versions
        if ($offset + 8 <= strlen($buffer)) {
            $tmp                = @unpack('N', substr($buffer, $offset, 4));
            $protocolVersion    = is_array($tmp) && isset($tmp[1]) ? (int) $tmp[1] : 0;
            $tmp                = @unpack('N', substr($buffer, $offset + 4, 4));
            $minProtocolVersion = is_array($tmp) && isset($tmp[1]) ? (int) $tmp[1] : 0;
            $offset += 8;

            // Read build version
            if ($offset + 4 <= strlen($buffer)) {
                $tmp          = @unpack('N', substr($buffer, $offset, 4));
                $buildVersion = is_array($tmp) && isset($tmp[1]) ? (int) $tmp[1] : 0;
                $offset += 4;

                // Read server name (up to 24 chars, null terminated)
                $serverName = '';
                $maxLen     = min(24, strlen($buffer) - $offset);

                for ($i = 0; $i < $maxLen && ord($buffer[$offset + $i]) !== 0; $i++) {
                    $serverName .= $buffer[$offset + $i];
                }

                $this->servertitle = $serverName;

                return true;
            }
        }

        return false;
    }

    private function processInfoResponse(string $buffer): bool
    {
        if (strlen($buffer) < 10) {
            return false;
        }

        // Parse info response based on Torque protocol
        // Format: packet_type(1) + flags(1) + key(4) + game_type + mission_type + mission_name + status(1) + num_players(1) + max_players(1) + num_bots(1) + cpu_speed(2) + ...

        $offset = 6; // Skip packet_type, flags, key

        // Read strings (null terminated)
        $strings = [];

        for ($i = 0; $i < 3; $i++) { // game_type, mission_type, mission_name
            $str = '';

            while ($offset < strlen($buffer) && ord($buffer[$offset]) !== 0) {
                $str .= $buffer[$offset];
                $offset++;
            }
            $offset++; // Skip null
            $strings[] = $str;
        }

        if (count($strings) >= 3) {
            $this->gametype              = $strings[0];
            /** @phpstan-ignore offsetAccess.notFound */
            $this->rules['mission_type'] = $strings[1];
            /** @phpstan-ignore offsetAccess.notFound */
            $this->mapname               = $strings[2];
        }

        // Read status byte
        if ($offset < strlen($buffer)) {
            $status = ord($buffer[$offset]);
            $offset++;
            $this->rules['status'] = $status;
        }

        // Read player counts
        if ($offset + 2 <= strlen($buffer)) {
            $this->numplayers = ord($buffer[$offset]);
            $this->maxplayers = ord($buffer[$offset + 1]);
            $offset += 2;
        }

        // Read bot count
        if ($offset < strlen($buffer)) {
            $this->rules['num_bots'] = ord($buffer[$offset]);
            $offset++;
        }

        // Read CPU speed
        if ($offset + 2 <= strlen($buffer)) {
            $tmp                      = @unpack('n', substr($buffer, $offset, 2));
            $cpuSpeed                 = is_array($tmp) && isset($tmp[1]) ? (int) $tmp[1] : 0;
            $this->rules['cpu_speed'] = $cpuSpeed;
            $offset += 2;
        }

        return true;
    }
}
