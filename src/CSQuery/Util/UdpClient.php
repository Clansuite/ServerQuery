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

namespace Clansuite\ServerQuery\Util;

use function fclose;
use function fread;
use function fsockopen;
use function fwrite;
use function is_array;
use function ord;
use function stream_get_meta_data;
use function stream_set_blocking;
use function stream_set_timeout;
use function strlen;
use function substr;
use function unpack;
use function usleep;

/**
 * UDP Client for game server queries.
 *
 * @method string[] queryMultiPacket(string $address, int $port, string $packet, int $maxPackets = 0, float $interPacketTimeout = 0.1)
 */
class UdpClient
{
    private int $timeout = 10; // default timeout in seconds

    /**
     * setTimeout method.
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * Send a UDP query and receive response.
     */
    public function query(string $address, int $port, string $packet): ?string
    {
        $fp = $this->createSocket($address, $port);

        if ($fp === false) {
            return null;
        }

        stream_set_blocking($fp, true);
        stream_set_timeout($fp, $this->timeout, 0);

        // Send packet
        if (fwrite($fp, $packet, strlen($packet)) === false) {
            fclose($fp);

            return null;
        }

        $result       = '';
        $socketstatus = stream_get_meta_data($fp);

        while (!$socketstatus['timed_out'] && !$socketstatus['eof']) {
            $result .= fread($fp, 128);
            $socketstatus = stream_get_meta_data($fp);
        }

        fclose($fp);

        if ($result === '' || $result === '0') {
            return null;
        }

        return $result;
    }

    /**
     * Send a UDP query and receive multiple packets.
     * Some protocols send multiple separate UDP packets in response to a single query.
     *
     * @param string $address Server address
     * @param int    $port    server port
     *
     * @return ?array<mixed>
     */
    public function queryPlayers(string $address, int $port): ?array
    {
        // Step 1: Get challenge
        $challengePacket   = "\xFF\xFF\xFF\xFF\x55\xFF\xFF\xFF\xFF";
        $challengeResponse = $this->query($address, $port, $challengePacket);

        if ($challengeResponse === null || $challengeResponse === '' || $challengeResponse === '0' || strlen($challengeResponse) < 9) {
            return null;
        }

        $challenge = substr($challengeResponse, 5, 4);

        // Step 2: Query players with challenge
        $playerPacket   = "\xFF\xFF\xFF\xFF\x55" . $challenge;
        $playerResponse = $this->query($address, $port, $playerPacket);

        if ($playerResponse === null || $playerResponse === '' || $playerResponse === '0' || strlen($playerResponse) < 6) {
            return null;
        }

        // Parse response
        return $this->parsePlayerResponse($playerResponse);
    }

    /**
     * Send a UDP query and receive multiple packets.
     *
     * @param string $address            Server address
     * @param int    $port               Server port
     * @param string $packet             Packet to send
     * @param int    $maxPackets         Maximum number of packets to receive (0 for unlimited)
     * @param float  $interPacketTimeout Timeout between packets
     *
     * @return string[] Array of received packets
     */
    public function queryMultiPacket(string $address, int $port, string $packet, int $maxPackets = 0, float $interPacketTimeout = 0.1): array
    {
        $fp = $this->createSocket($address, $port);

        if ($fp === false) {
            return [];
        }

        stream_set_blocking($fp, true);
        stream_set_timeout($fp, $this->timeout, 0);

        // Send packet
        if (fwrite($fp, $packet, strlen($packet)) === false) {
            fclose($fp);

            return [];
        }

        $packets     = [];
        $packetCount = 0;

        while ($maxPackets === 0 || $packetCount < $maxPackets) {
            $result       = '';
            $socketstatus = stream_get_meta_data($fp);

            while (!$socketstatus['timed_out'] && !$socketstatus['eof']) {
                $data = fread($fp, 128);

                if ($data === false || $data === '') {
                    break;
                }
                $result .= $data;
                $socketstatus = stream_get_meta_data($fp);
            }

            if ($result === '' || $result === '0') {
                break;
            }

            $packets[] = $result;
            $packetCount++;

            // Wait for inter-packet timeout
            if ($interPacketTimeout > 0) {
                usleep((int) ($interPacketTimeout * 1000000));
            }
        }

        fclose($fp);

        return $packets;
    }

    /**
     * Create a UDP socket connection.
     *
     * @return false|resource returns a socket resource on success, or false on failure
     *
     * @phpstan-return resource|false
     *
     * @psalm-return resource|false
     *
     * @phpstan-ignore-next-line you can not annotate ": bool|resource" to fix it!
     */
    protected function createSocket(string $address, int $port)
    {
        $socket = @fsockopen('udp://' . $address, $port, $errno, $errstr, $this->timeout);

        if ($socket === false) {
            return false;
        }

        return $socket;
    }

    /**
     * Parse A2S_PLAYER response.
     *
     * @return array<mixed>
     */
    private function parsePlayerResponse(string $data): array
    {
        $data       = substr($data, 5); // Skip header
        $numPlayers = ord($data[0]);
        $offset     = 1;
        $players    = [];

        for ($i = 0; $i < $numPlayers; $i++) {
            if ($offset >= strlen($data)) {
                break;
            }

            $index = ord($data[$offset]);
            $offset++;

            // Player name (null-terminated)
            $name = '';

            while ($offset < strlen($data) && $data[$offset] !== "\x00") {
                $name .= $data[$offset];
                $offset++;
            }
            $offset++; // Skip null

            if ($offset + 8 > strlen($data)) {
                break;
            }

            // Score (int32 little-endian)
            $unpackedScore = unpack('l', substr($data, $offset, 4));

            if (!is_array($unpackedScore) || !isset($unpackedScore[1])) {
                break;
            }
            $score = $unpackedScore[1];
            $offset += 4;

            // Time connected (float32 little-endian)
            $unpackedTime = unpack('f', substr($data, $offset, 4));

            if (!is_array($unpackedTime) || !isset($unpackedTime[1])) {
                break;
            }
            $time = $unpackedTime[1];
            $offset += 4;

            $players[] = [
                'index' => $index,
                'name'  => $name,
                'score' => $score,
                'time'  => $time,
            ];
        }

        return $players;
    }
}
