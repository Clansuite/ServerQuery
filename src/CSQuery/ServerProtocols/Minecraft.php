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

use function array_filter;
use function chr;
use function count;
use function explode;
use function fclose;
use function fread;
use function fsockopen;
use function fwrite;
use function is_array;
use function is_string;
use function json_decode;
use function ord;
use function pack;
use function random_int;
use function stream_set_timeout;
use function strlen;
use function substr;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Minecraft server protocol implementation.
 *
 * The SLP protocol does not provide server variables or rules.
 * These are only available through the Legacy Query protocol,
 * which requires the server administrator to explicitly enable
 * query in server.properties with enable-query=true.
 *
 * Protocols:
 * - SLP (Serer List Ping) provides only basic infos.
 * - Legacy Query: More detailed server variables.
 *
 * @see https://minecraft.wiki/w/Query
 */
class Minecraft extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Minecraft';

    /**
     * List of supported games.
     */
    public array $supportedGames = ['Minecraft'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'minecraft';

    /**
     * Constructor.
     *
     * @param $address         Server address
     * @param $queryport       Query port
     * @param $protocolVersion Protocol version to use: 'slp' (Server List Ping) or 'legacy' (Legacy Query)
     */
    public function __construct(?string $address = null, ?int $queryport = null, public string $protocolVersion = 'slp')
    {
        parent::__construct($address, $queryport);
    }

    /**
     * Query server information.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        if ($this->online) {
            $this->reset();
        }

        if ($this->protocolVersion === 'legacy') {
            return $this->queryLegacy();
        }

        return $this->querySLP();
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
     * Query using Legacy Query protocol (UDP).
     */
    private function queryLegacy(): bool
    {
        $address = (string) $this->address;
        $port    = (int) $this->queryport;

        // Generate session ID
        $sessionId = random_int(1, 0x7FFFFFFF);
        // Send handshake
        $handshake = pack('c3N', 0xFE, 0xFD, 0x09, $sessionId);
        $response  = $this->sendCommand($address, $port, $handshake);

        if ($response === false || strlen($response) < 5) {
            $this->errstr = 'No handshake response from Minecraft server';

            return false;
        }

        // Parse challenge token
        if ($response[0] !== chr(0x09)) {
            $this->errstr = 'Invalid handshake response';

            return false;
        }
        $challengeToken = (int) substr($response, 1, -1);
        // Send full query
        $fullQuery = pack('c3N', 0xFE, 0xFD, 0x00, $sessionId) .
                     pack('N', $challengeToken) .
                     pack('c2', 0x00, 0x00);
        $response = $this->sendCommand($address, $port, $fullQuery);

        if ($response === false || strlen($response) < 5) {
            $this->errstr = 'No query response from Minecraft server';

            return false;
        }

        // Parse response
        if ($response[0] !== chr(0x00)) {
            $this->errstr = 'Invalid query response';

            return false;
        }
        $this->parseLegacyResponse(substr($response, 1));
        $this->online = true;

        return true;
    }

    /**
     * Query using Server List Ping (TCP JSON).
     */
    private function querySLP(): bool
    {
        $host = (string) $this->address;
        $port = (int) $this->queryport;

        $errno  = 0;
        $errstr = '';

        $fp = @fsockopen($host, $port, $errno, $errstr, 5);

        if ($fp === false) {
            $this->errstr = 'Unable to connect to Minecraft server';

            return false;
        }
        stream_set_timeout($fp, 5);
        // Send handshake
        $handshake = $this->buildHandshakePacket();
        fwrite($fp, $handshake);
        // Send status request
        $statusRequest = $this->buildStatusRequestPacket();
        fwrite($fp, $statusRequest);
        // Read response
        $response = $this->readPacket($fp);

        if ($response === false) {
            fclose($fp);
            $this->errstr = 'No response from Minecraft server';

            return false;
        }
        $ptr = 1;
        // Skip ID
        $jsonLength = $this->readVarIntFromString($response, $ptr);
        $json       = substr($response, $ptr, $jsonLength);
        $data       = json_decode($json, true);

        if ($data === null) {
            fclose($fp);
            $this->errstr = 'Invalid JSON response';

            return false;
        }

        if (!is_array($data)) {
            fclose($fp);
            $this->errstr = 'Invalid JSON response structure';

            return false;
        }

        $this->parseSLPResponse($data);
        $this->online = true;
        fclose($fp);

        return true;
    }

    private function buildHandshakePacket(): string
    {
        $handshakeData = pack('c', 0) . // Packet ID
                         $this->writeVarInt(0) . // Protocol version 0
                         $this->writeString((string) $this->address) . // Server address
                         pack('n', (int) $this->queryport) . // Server port
                         pack('c', 1); // Next state (status)

        return $this->writeVarInt(strlen($handshakeData)) . $handshakeData;
    }

    private function buildStatusRequestPacket(): string
    {
        $data = pack('c', 0); // Packet ID

        return $this->writeVarInt(strlen($data)) . $data;
    }

    /**
     * @param resource $fp
     */
    private function readPacket(mixed $fp): false|string
    {
        $length = $this->readVarInt($fp);

        if ($length === false || $length <= 0) {
            return false;
        }

        $data = '';

        while (strlen($data) < $length) {
            /** @phpstan-ignore argument.type */
            $chunk = fread($fp, $length - strlen($data));

            if ($chunk === false || $chunk === '') {
                return false;
            }
            $data .= $chunk;
        }

        return $data;
    }

    /**
     * @param array<mixed> $data
     */
    private function parseSLPResponse(array $data): void
    {
        $description = $data['description'] ?? '';

        if (is_array($description)) {
            // Handle formatted text objects
            $this->servertitle = $this->parseFormattedText($description);
        } else {
            $this->servertitle = (string) $description;
        }

        $playersData = $data['players'] ?? [];

        if (is_array($playersData)) {
            $this->numplayers = (int) ($playersData['online'] ?? 0);
            $this->maxplayers = (int) ($playersData['max'] ?? 0);

            if (isset($playersData['sample']) && is_array($playersData['sample'])) {
                $this->players = [];

                foreach ($playersData['sample'] as $player) {
                    if (is_array($player)) {
                        $this->players[] = [
                            'name' => (string) ($player['name'] ?? ''),
                            'id'   => (string) ($player['id'] ?? ''),
                        ];
                    }
                }
            }
        }

        $versionData = $data['version'] ?? [];

        if (is_array($versionData)) {
            $this->gameversion = (string) ($versionData['name'] ?? '');
        }
    }

    private function parseLegacyResponse(string $data): void
    {
        // Split by null bytes
        $parts = explode("\0", $data);
        $parts = array_filter($parts, static fn (string $v): bool => $v !== ''); // Remove empty parts

        $this->rules = [];

        for ($i = 0; $i < count($parts); $i += 2) {
            $key   = $parts[$i] ?? '';
            $value = $parts[$i + 1] ?? '';

            // Map to standard fields
            switch ($key) {
                case 'hostname':
                    $this->servertitle = $value;

                    break;

                case 'gametype':
                    $this->gametype = $value;

                    break;

                case 'game_id':
                    $this->gamename = $value;

                    break;

                case 'version':
                    $this->gameversion = $value;

                    break;

                case 'plugins':
                    // Parse plugins if present
                    $this->rules['plugins'] = $value;

                    break;

                case 'map':
                    $this->mapname = $value;

                    break;

                case 'numplayers':
                    $this->numplayers = (int) $value;

                    break;

                case 'maxplayers':
                    $this->maxplayers = (int) $value;

                    break;

                case 'hostport':
                    $this->hostport = (int) $value;

                    break;

                default:
                    $this->rules[$key] = $value;

                    break;
            }
        }

        // Players are not provided in legacy query basic response
        $this->players = [];
    }

    private function writeVarInt(int $value): string
    {
        $result = '';

        do {
            $byte = $value & 0x7F;
            $value >>= 7;

            if ($value !== 0) {
                $byte |= 0x80;
            }
            $result .= chr($byte);
        } while ($value !== 0);

        return $result;
    }

    /**
     * @param resource $fp
     */
    private function readVarInt(mixed $fp): false|int
    {
        $value = 0;
        $shift = 0;

        while (true) {
            $byte = fread($fp, 1);

            if ($byte === false || $byte === '') {
                return false;
            }

            $byte = ord($byte);
            $value |= ($byte & 0x7F) << $shift;
            $shift += 7;

            if ((($byte & 0x80) === 0)) {
                break;
            }

            if ($shift >= 35) {
                return false; // VarInt too big
            }
        }

        return $value;
    }

    private function readVarIntFromString(string $buffer, int &$ptr): int
    {
        $value = 0;
        $shift = 0;

        while (true) {
            $byte = ord($buffer[$ptr++]);
            $value |= ($byte & 0x7F) << $shift;
            $shift += 7;

            if ((($byte & 0x80) === 0)) {
                break;
            }

            if ($shift >= 35) {
                return 0; // Error
            }
        }

        return $value;
    }

    private function writeString(string $string): string
    {
        return $this->writeVarInt(strlen($string)) . $string;
    }

    /**
     * @param array<mixed> $textObject
     */
    private function parseFormattedText(array $textObject): string
    {
        $text = (string) ($textObject['text'] ?? '');

        $extra = $textObject['extra'] ?? null;

        if (is_array($extra)) {
            foreach ($extra as $item) {
                if (is_array($item) && isset($item['text'])) {
                    $text .= (string) ($item['text'] ?? '');
                } elseif (is_string($item)) {
                    $text .= $item;
                }
            }
        }

        return $text;
    }
}
