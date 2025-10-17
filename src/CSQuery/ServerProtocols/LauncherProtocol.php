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
use function define;
use function is_array;
use function ord;
use function pack;
use function strlen;
use function substr;
use function time;
use function unpack;
use Clansuite\ServerQuery\CSQuery;
use Clansuite\ServerQuery\Util\HuffmanDecoder;
use Exception;
use Override;
use RuntimeException;

/**
 * Base class for Launcher Protocol.
 *
 * Implements the Launcher Protocol used by Skulltag and Zandronum servers.
 *
 * Zandronum is backward compatible with Skulltag's query structure.
 */
abstract class LauncherProtocol extends CSQuery
{
    /**
     * Game name for this protocol.
     */
    protected string $gameName;

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

        // Query the server using the Launcher Protocol
        $info = $this->queryLauncherServer((string) $this->address, (int) $this->queryport, $getPlayers);

        if ($info === [] || isset($info['error'])) {
            $this->errstr = (string) ($info['error'] ?? 'No response received');

            return false;
        }

        // Parse the response data
        $this->parseResponseData($info, $getPlayers);
        $this->online = true;

        return true;
    }

    /**
     * Parse response data into CSQuery properties.
     *
     * @param array<mixed> $info
     */
    protected function parseResponseData(array $info, bool $getPlayers): void
    {
        $this->gamename    = $this->gameName;
        $this->gameversion = (string) ($info['version'] ?? '');

        if (isset($info['name'])) {
            $this->servertitle = (string) $info['name'];
        }

        if (isset($info['numPlayers'])) {
            $this->numplayers = (int) $info['numPlayers'];
            $this->maxplayers = 64; // Default max, could be parsed from server info if available
        }

        if ($getPlayers && isset($info['players'])) {
            $this->players = [];

            foreach ($info['players'] as $player) {
                if (is_array($player)) {
                    $this->players[] = [
                        'name'           => (string) ($player['name'] ?? ''),
                        'score'          => (int) ($player['frags'] ?? 0),
                        'ping'           => (int) ($player['ping'] ?? 0),
                        'team'           => (string) ($player['team'] ?? ''),
                        'time_in_server' => (int) ($player['time_in_server'] ?? 0),
                        'spectator'      => (bool) ($player['spectator'] ?? false),
                        'bot'            => (bool) ($player['bot'] ?? false),
                    ];
                }
            }
        }
    }

    /**
     * Query server using Launcher Protocol.
     *
     * @return array<mixed>
     */
    private function queryLauncherServer(string $ip, int $port, bool $getPlayers = true): array
    {
        // Choose flags: server name + player count + player data
        $flags = SQF_NAME | SQF_NUMPLAYERS;

        if ($getPlayers) {
            $flags |= SQF_PLAYERDATA;
        }

        $time = time();

        // Build uncompressed query packet
        $unencodedPacket = $this->packLong(LAUNCHER_CHALLENGE);
        $unencodedPacket .= $this->packLong($flags);
        $unencodedPacket .= $this->packLong($time);
        $unencodedPacket .= $this->packLong(0); // extended_flags

        // Huffman-encode the query packet
        $encoder = new HuffmanDecoder;
        $packet  = $encoder->compress($unencodedPacket);

        // Send query using UDP client
        $response = $this->udpClient->query($ip, $port, $packet);

        if ($response === null || $response === '' || $response === '0') {
            return ['error' => 'No response received'];
        }

        // Step 1: Huffman decompress the response
        $decoder = new HuffmanDecoder;

        try {
            $decompressed = $decoder->decompress($response);
        } catch (Exception $e) {
            return ['error' => 'Huffman decompression failed: ' . $e->getMessage()];
        }

        // Step 2: Parse response
        return $this->parseDecompressedData($decompressed);
    }

    /**
     * Parse decompressed response data.
     *
     * @return array<mixed>
     */
    private function parseDecompressedData(string $data): array
    {
        $offset = 0;

        $responseCode = $this->getLong($data, $offset);

        // Handle different response codes
        if ($responseCode === 5660024) { // SERVER_LAUNCHER_IGNORING
            return ['error' => 'Request ignored. Try again later.'];
        }

        if ($responseCode === 5660025) { // SERVER_LAUNCHER_BANNED
            return ['error' => 'Your IP is banned from this server.'];
        }

        if ($responseCode === 5660031) { // SERVER_LAUNCHER_SEGMENTED_CHALLENGE
            // Handle segmented response
            return $this->parseSegmentedResponse();
        }

        if ($responseCode !== 5660023) { // SERVER_LAUNCHER_CHALLENGE
            return ['error' => "Invalid response code ({$responseCode}), expected 5660023"];
        }

        // Parse regular response
        $this->getLong($data, $offset);
        $version       = $this->getString($data, $offset);
        $returnedFlags = $this->getLong($data, $offset);

        $result = [
            'version' => $version,
            'players' => [],
        ];

        if (($returnedFlags & SQF_NAME) !== 0) {
            $result['name'] = $this->getString($data, $offset);
        }

        if (($returnedFlags & SQF_NUMPLAYERS) !== 0) {
            $numPlayers           = $this->getByte($data, $offset);
            $result['numPlayers'] = $numPlayers;
        }

        if (($returnedFlags & SQF_PLAYERDATA) !== 0 && isset($result['numPlayers'])) {
            for ($i = 0; $i < $result['numPlayers']; $i++) {
                $player                   = [];
                $player['name']           = $this->getString($data, $offset);
                $player['frags']          = $this->getShort($data, $offset);
                $player['ping']           = $this->getShort($data, $offset);
                $player['spectator']      = $this->getByte($data, $offset);
                $player['bot']            = $this->getByte($data, $offset);
                $player['team']           = $this->getByte($data, $offset);
                $player['time_in_server'] = $this->getByte($data, $offset); // seconds
                $result['players'][]      = $player;
            }
        }

        return $result;
    }

    /**
     * Parse segmented response data.
     *
     * @return array<mixed>
     */
    private function parseSegmentedResponse(): array
    {
        // For now, return error - segmented responses need more complex handling
        return ['error' => 'Segmented responses not yet implemented'];
    }

    /**
     * Helper: pack a 32-bit little endian.
     */
    private function packLong(int $val): string
    {
        return pack('V', $val); // little-endian 32-bit
    }

    /**
     * Helper: get 32-bit little endian from data.
     */
    private function getLong(string $data, int &$offset): int
    {
        $unpacked = unpack('V', substr($data, $offset, 4));

        if (!is_array($unpacked) || !isset($unpacked[1])) {
            throw new RuntimeException('Invalid data for getLong');
        }
        $val = (int) $unpacked[1];
        $offset += 4;

        return $val;
    }

    /**
     * Helper: get 16-bit little endian from data.
     */
    private function getShort(string $data, int &$offset): int
    {
        $unpacked = unpack('v', substr($data, $offset, 2));

        if (!is_array($unpacked) || !isset($unpacked[1])) {
            throw new RuntimeException('Invalid data for getShort');
        }
        $val = (int) $unpacked[1];
        $offset += 2;

        return $val;
    }

    /**
     * Helper: get byte from data.
     */
    private function getByte(string $data, int &$offset): int
    {
        $val = ord($data[$offset]);
        $offset++;

        return $val;
    }

    /**
     * Helper: get null-terminated string from data.
     */
    private function getString(string $data, int &$offset): string
    {
        $out = '';

        while ($offset < strlen($data) && $data[$offset] !== "\x00") {
            $out .= $data[$offset];
            $offset++;
        }
        $offset++; // skip null terminator

        return $out;
    }
}

/**
 * Protocol Constants.
 */
define('LAUNCHER_CHALLENGE', 199);

/**
 * Query flags.
 */
define('SQF_NAME', 0x00000001);
define('SQF_NUMPLAYERS', 0x00080000);
define('SQF_PLAYERDATA', 0x00100000);
