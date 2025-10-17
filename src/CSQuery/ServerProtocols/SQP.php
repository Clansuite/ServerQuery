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
use function is_float;
use function is_int;
use function is_string;
use function pack;
use function strlen;
use function substr;
use function unpack;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * SQP (Server Query Protocol) implementation.
 *
 * This is based on Unity Technologies' SQP protocol.
 * Used for querying Unity-based game servers, like TF2E, Unturned, etc.
 *
 * @see https://docs.unity.com/ugs/en-us/manual/game-server-hosting/manual/concepts/sqp
 */
class SQP extends CSQuery
{
    /**
     * SQP constants.
     */
    public const QueryRequestType = 0x00;

    public const QueryResponseType     = 0x00;
    public const ChallengeRequestType  = 0x01;
    public const ChallengeResponseType = 0x01;
    public const Version               = 1;
    public const DefaultMaxPacketSize  = 1400;

    // Chunk types
    public const ServerInfo  = 0x01;
    public const ServerRules = 0x02;
    public const PlayerInfo  = 0x04;
    public const TeamInfo    = 0x08;
    public const Metrics     = 0x10;

    /**
     * Protocol name.
     */
    public string $name = 'SQP';

    /**
     * Protocol identifier.
     */
    public string $protocol = 'SQP';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Unity'];

    /**
     * Challenge ID.
     */
    private int $challengeID = 0;

    /**
     * Constructor.
     */
    public function __construct(string $address, int $queryport)
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

        // Get challenge
        if (!$this->getChallenge()) {
            return false;
        }

        // Build requested chunks
        $requestedChunks = self::ServerInfo;

        if ($getRules) {
            $requestedChunks |= self::ServerRules;
        }

        if ($getPlayers) {
            $requestedChunks |= self::PlayerInfo;
        }

        // Send query
        $queryPacket = $this->buildQueryPacket($requestedChunks);

        if (($result = $this->sendCommand($address, $port, $queryPacket)) === '' || ($result = $this->sendCommand($address, $port, $queryPacket)) === '0' || ($result = $this->sendCommand($address, $port, $queryPacket)) === false) {
            return false;
        }

        // Parse response
        return $this->parseResponse($result, $requestedChunks);
    }

    private function getChallenge(): bool
    {
        $address = (string) $this->address;
        $port    = (int) $this->queryport;

        $challengePacket = pack('C', self::ChallengeRequestType);

        if (($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $challengePacket)) === '' || ($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $challengePacket)) === '0' || ($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $challengePacket)) === false) {
            return false;
        }

        if (strlen($result) < 5) {
            $this->errstr = 'Invalid challenge response';

            return false;
        }

        $data = @unpack('Ctype/Nchallenge', $result);

        if (!is_array($data) || !isset($data['type']) || !isset($data['challenge']) || !is_int($data['type']) || !is_int($data['challenge'])) {
            $this->errstr = 'Invalid challenge response';

            return false;
        }

        if ($data['type'] !== self::ChallengeResponseType) {
            $this->errstr = 'Invalid challenge response type';

            return false;
        }

        $this->challengeID = $data['challenge'];

        return true;
    }

    private function buildQueryPacket(int $requestedChunks): string
    {
        return pack(
            'CNNC',
            self::QueryRequestType,
            $this->challengeID,
            self::Version,
            $requestedChunks,
        );
    }

    private function parseResponse(string $data, int $requestedChunks): bool
    {
        $pos = 0;
        $len = strlen($data);
        $tmp = null;

        // Read header
        if (1 > $len) {
            return false;
        }
        $tmp = @unpack('C', substr($data, $pos, 1));

        if (!is_array($tmp) || !isset($tmp[1])) {
            return false;
        }
        $pktType = $tmp[1];
        $pos++;

        if ($pktType !== self::QueryResponseType) {
            $this->errstr = 'Invalid response type';

            return false;
        }

        // Validate challenge
        if ($pos + 4 > $len) {
            return false;
        }
        $tmp = @unpack('N', substr($data, $pos, 4));

        if (!is_array($tmp) || !isset($tmp[1])) {
            return false;
        }
        $challenge = $tmp[1];
        $pos += 4;

        if ($challenge !== $this->challengeID) {
            $this->errstr = 'Challenge mismatch';

            return false;
        }

        // Version
        if ($pos + 2 > $len) {
            return false;
        }
        $tmp = @unpack('n', substr($data, $pos, 2));

        if (!is_array($tmp) || !isset($tmp[1])) {
            return false;
        }
        unset($tmp); // Currently unused
        $pos += 2;

        // Packet info (assuming single packet for simplicity)
        if ($pos + 4 > $len) {
            return false;
        }
        $tmp = @unpack('C', substr($data, $pos, 1));

        if (!is_array($tmp) || !isset($tmp[1])) {
            return false;
        }
        $curPkt = $tmp[1];
        $pos++;
        $tmp = @unpack('C', substr($data, $pos, 1));

        if (!is_array($tmp) || !isset($tmp[1])) {
            return false;
        }
        $lastPkt = $tmp[1];
        $pos++;
        $tmp = @unpack('n', substr($data, $pos, 2));

        if (!is_array($tmp) || !isset($tmp[1])) {
            return false;
        }
        $pktLen = $tmp[1];
        $pos += 2;

        if ($curPkt > $lastPkt) {
            $this->errstr = 'Invalid packet sequence';

            return false;
        }

        // Parse chunks
        $remaining = $pktLen;

        if (($requestedChunks & self::ServerInfo) !== 0) {
            $bytesRead = $this->parseServerInfo($data, $pos);

            if ($bytesRead === false) {
                return false;
            }
            $pos += $bytesRead;
            $remaining -= $bytesRead;
        }

        if (($requestedChunks & self::ServerRules) !== 0) {
            $bytesRead = $this->parseServerRules($data, $pos);

            if ($bytesRead === false) {
                return false;
            }
            $pos += $bytesRead;
            $remaining -= $bytesRead;
        }

        if (($requestedChunks & self::PlayerInfo) !== 0) {
            $bytesRead = $this->parsePlayerInfo($data, $pos);

            if ($bytesRead === false) {
                return false;
            }
            $pos += $bytesRead;
            $remaining -= $bytesRead;
        }

        // Skip remaining bytes
        $this->online = true;

        return true;
    }

    private function parseServerInfo(string $data, int $pos): false|int
    {
        $startPos = $pos;
        $tmp      = null;

        if ($pos + 4 > strlen($data)) {
            return false;
        }
        $tmp = @unpack('N', substr($data, $pos, 4));

        if (!is_array($tmp) || !isset($tmp[1])) {
            return false;
        }
        $this->challengeID = $tmp[1];
        $pos += 4;

        if ($pos + 2 > strlen($data)) {
            return false;
        }
        $tmp = @unpack('n', substr($data, $pos, 2));

        if (!is_array($tmp) || !isset($tmp[1])) {
            return false;
        }
        $this->numplayers = $tmp[1];
        $pos += 2;

        if ($pos + 2 > strlen($data)) {
            return false;
        }
        $tmp = @unpack('n', substr($data, $pos, 2));

        if (!is_array($tmp) || !isset($tmp[1])) {
            return false;
        }
        $this->maxplayers = $tmp[1];
        $pos += 2;

        $this->servertitle = $this->readString($data, $pos);
        $this->gametype    = $this->readString($data, $pos);
        $this->readString($data, $pos);
        $this->mapname = $this->readString($data, $pos);

        if ($pos + 2 > strlen($data)) {
            return false;
        }
        $tmp = @unpack('n', substr($data, $pos, 2));

        if (!is_array($tmp) || !isset($tmp[1])) {
            return false;
        }
        $this->hostport = $tmp[1];
        $pos += 2;

        return $pos - $startPos;
    }

    private function parseServerRules(string $data, int &$pos): false|int
    {
        $startPos = $pos;
        $tmp      = null;

        if ($pos + 4 > strlen($data)) {
            return false;
        }
        $tmp = @unpack('N', substr($data, $pos, 4));

        if (!is_array($tmp) || !isset($tmp[1])) {
            return false;
        }
        $chunkLen = $tmp[1];
        $pos += 4;

        $endPos = $startPos + 4 + $chunkLen;

        while ($pos < $endPos) {
            $key               = $this->readString($data, $pos);
            $value             = $this->readString($data, $pos);
            $this->rules[$key] = $value;
        }

        return $pos - $startPos;
    }

    private function parsePlayerInfo(string $data, int &$pos): false|int
    {
        $startPos = $pos;
        $tmp      = null;

        if ($pos + 4 > strlen($data)) {
            return false;
        }
        $tmp = @unpack('N', substr($data, $pos, 4));

        if (!is_array($tmp) || !isset($tmp[1])) {
            return false;
        }
        $chunkLen = $tmp[1];
        $pos += 4;

        if ($pos + 2 > strlen($data)) {
            return false;
        }
        $tmp = @unpack('n', substr($data, $pos, 2));

        if (!is_array($tmp) || !isset($tmp[1])) {
            return false;
        }
        $playerCount = $tmp[1];
        $pos += 2;

        if ($playerCount === 0) {
            return $pos - $startPos + $chunkLen - 6; // Skip chunk
        }

        // Read header
        $header = $this->readInfoHeader($data, $pos);

        // Read players
        for ($i = 0; $i < $playerCount; $i++) {
            $player = [];

            foreach ($header as $field) {
                $type = $field['type'] ?? 0;
                $name = $field['name'] ?? '';

                if (is_int($type) && is_string($name)) {
                    $value         = $this->readDynamicValue($data, $pos, $type);
                    $player[$name] = $value;
                }
            }
            $this->players[] = $player;
        }

        return $pos - $startPos;
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function readInfoHeader(string $data, int &$pos): array
    {
        $tmp = null;
        $tmp = @unpack('C', substr($data, $pos, 1));
        $pos++;
        $fieldCount = 0;

        if (is_array($tmp) && isset($tmp[1])) {
            $fieldCount = $tmp[1];
        }
        $header = [];

        for ($i = 0; $i < $fieldCount; $i++) {
            $name = $this->readString($data, $pos);
            $tmp  = @unpack('C', substr($data, $pos, 1));
            $pos++;
            $type = 0;

            if (is_array($tmp) && isset($tmp[1])) {
                $type = $tmp[1];
            }
            $header[] = ['name' => $name, 'type' => $type];
        }

        return $header;
    }

    private function readDynamicValue(string $data, int &$pos, int $type): null|float|int|string
    {
        $tmp = null;

        switch ($type) {
            case 0: // String
                return $this->readString($data, $pos);

            case 1: // Uint8
                $tmp = @unpack('C', $data[$pos] ?? "\x00");
                $pos++;
                $val = 0;

                if (is_array($tmp) && isset($tmp[1]) && is_int($tmp[1])) {
                    $val = $tmp[1];
                }

                return $val;

            case 2: // Uint16
                $tmp = @unpack('n', substr($data, $pos, 2));
                $pos += 2;
                $val = 0;

                if (is_array($tmp) && isset($tmp[1]) && is_int($tmp[1])) {
                    $val = $tmp[1];
                }

                return $val;

            case 3: // Uint32
                $tmp = @unpack('N', substr($data, $pos, 4));
                $pos += 4;
                $val = 0;

                if (is_array($tmp) && isset($tmp[1]) && is_int($tmp[1])) {
                    $val = $tmp[1];
                }

                return $val;

            case 4: // Uint64
                $tmp = @unpack('J', substr($data, $pos, 8));
                $pos += 8;
                $val = 0;

                if (is_array($tmp) && isset($tmp[1]) && is_int($tmp[1])) {
                    $val = $tmp[1];
                }

                return $val;

            case 5: // Int8
                $tmp = @unpack('c', $data[$pos] ?? "\x00");
                $pos++;
                $val = 0;

                if (is_array($tmp) && isset($tmp[1]) && is_int($tmp[1])) {
                    $val = $tmp[1];
                }

                return $val;

            case 6: // Int16
                $tmp = @unpack('s', substr($data, $pos, 2));
                $pos += 2;
                $val = 0;

                if (is_array($tmp) && isset($tmp[1]) && is_int($tmp[1])) {
                    $val = $tmp[1];
                }

                return $val;

            case 7: // Int32
                $tmp = @unpack('l', substr($data, $pos, 4));
                $pos += 4;
                $val = 0;

                if (is_array($tmp) && isset($tmp[1]) && is_int($tmp[1])) {
                    $val = $tmp[1];
                }

                return $val;

            case 8: // Int64
                $tmp = @unpack('q', substr($data, $pos, 8));
                $pos += 8;
                $val = 0;

                if (is_array($tmp) && isset($tmp[1]) && is_int($tmp[1])) {
                    $val = $tmp[1];
                }

                return $val;

            case 9: // Float32
                $tmp = @unpack('f', substr($data, $pos, 4));
                $pos += 4;
                $val = 0.0;

                if (is_array($tmp) && isset($tmp[1]) && is_float($tmp[1])) {
                    $val = $tmp[1];
                }

                return $val;

            case 10: // Float64
                $tmp = @unpack('d', substr($data, $pos, 8));
                $pos += 8;
                $val = 0.0;

                if (is_array($tmp) && isset($tmp[1]) && is_float($tmp[1])) {
                    $val = $tmp[1];
                }

                return $val;

            default:
                return null;
        }
    }

    private function readString(string $data, int &$pos): string
    {
        $start = $pos;

        while ($pos < strlen($data) && $data[$pos] !== "\x00") {
            $pos++;
        }
        $str = substr($data, $start, $pos - $start);
        $pos++; // Skip null terminator

        return $str;
    }
}
