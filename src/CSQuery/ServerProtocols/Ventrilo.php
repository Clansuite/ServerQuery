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

use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Exception;
use Override;
use RuntimeException;
use function array_combine;
use function array_pad;
use function chr;
use function count;
use function define;
use function defined;
use function explode;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function ksort;
use function pack;
use function preg_replace_callback;
use function preg_split;
use function socket_close;
use function socket_create;
use function socket_recvfrom;
use function socket_sendto;
use function socket_set_option;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function trim;
use function unpack;

/**
 * Queries Ventrilo voice communication servers.
 *
 * Retrieves server information including status, connected clients, channels, and settings
 * by sending a specific UDP query packet and parsing the encrypted response.
 * Enables monitoring and display of Ventrilo server details in game server query systems.
 */
class Ventrilo extends CSQuery implements ProtocolInterface
{
    public string $name = 'Ventrilo';

    /** @var array<string> */
    public array $supportedGames = ['Ventrilo'];
    public string $protocol      = 'ventrilo';

    /**
     * @var array<string>
     */
    public array $channels = [];

    /**
     * Initializes the Ventrilo query instance.
     *
     * @param null|string $address   The server address to query
     * @param null|int    $queryport The query port for the Ventrilo server
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct();
        $this->address   = $address ?? '';
        $this->queryport = $queryport ?? 0;
    }

    /**
     * Queries the Ventrilo server and populates server information.
     *
     * Sends a UDP query packet to the server and processes the encrypted response
     * to extract server details, player information, and rules.
     *
     * @param bool $getPlayers Whether to retrieve player information
     * @param bool $getRules   Whether to retrieve server rules/settings
     *
     * @return bool True on successful query, false on failure
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        // We'll use UDP sockets to send the known Ventrilo status packet and receive response(s).

        // Build query packet
        $packet = "V\xC8\xF4\xF9`\xA2\x1E\xA5M\xFB\x03\xCCQN\xA1\x10\x95\xAF\xB2g\x17g\x812\xFBW\xFD\x8E\xD2\"r\x034z\xBB\x98";

        $ip   = (string) $this->address;
        $port = (int) $this->queryport;

        // Create UDP socket
        if (!defined('AF_INET')) {
            define('AF_INET', 2);
            define('SOCK_DGRAM', 2);
            define('SOL_UDP', 17);
            define('SOL_SOCKET', 1);
            define('SO_RCVTIMEO', 20);
        }
        $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if ($sock === false) {
            $this->errstr = 'Unable to create UDP socket for Ventrilo';

            return false;
        }

        // Set a short receive timeout via socket options if available
        @socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 3, 'usec' => 0]);

        // Send packet
        $sent = @socket_sendto($sock, $packet, strlen($packet), 0, $ip, $port);

        if ($sent === false) {
            socket_close($sock);
            $this->errstr = 'Failed to send Ventrilo query packet';

            return false;
        }

        // Try to receive multiple packets (Ventrilo may split into several). We'll collect up to 8 packets.
        $packets = [];

        for ($i = 0; $i < 8; $i++) {
            $buf      = '';
            $from     = '';
            $fromPort = 0;
            $recv     = @socket_recvfrom($sock, $buf, 8192, 0, $from, $fromPort);

            if ($recv === false || $recv === 0 || $buf === '') {
                break;
            }
            $packets[] = $buf;

            // if fewer than 8192 bytes received, likely last packet
            if ($recv < 8192) {
                break;
            }
        }

        socket_close($sock);

        if (count($packets) === 0) {
            $this->errstr = 'No response from Ventrilo server';

            return false;
        }

        try {
            $result = $this->processPackets($packets);
        } catch (Exception $e) {
            $this->errstr = 'Failed to parse Ventrilo response: ' . $e->getMessage();

            return false;
        }

        // Map result to our properties
        $this->servertitle = isset($result['name']) && is_string($result['name']) ? $result['name'] : (isset($result['gq_name']) && is_string($result['gq_name']) ? $result['gq_name'] : $this->address ?? '');
        $this->numplayers  = isset($result['clientcount']) && is_int($result['clientcount']) ? $result['clientcount'] : (isset($result['client_count']) && is_int($result['client_count']) ? $result['client_count'] : 0);
        $this->maxplayers  = isset($result['maxclients']) && is_int($result['maxclients']) ? $result['maxclients'] : (isset($result['max_players']) && is_int($result['max_players']) ? $result['max_players'] : 0);
        $this->players     = isset($result['players']) && is_array($result['players']) ? $result['players'] : [];
        $this->channels    = isset($result['teams']) && is_array($result['teams']) ? $result['teams'] : [];
        $this->online      = true;

        return true;
    }

    /**
     * Performs a query on the specified Ventrilo server address.
     *
     * Updates internal state with server information and returns a ServerInfo object
     * containing the query results.
     *
     * @param ServerAddress $addr The server address and port to query
     *
     * @return ServerInfo Server information including status, players, and settings
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
            servertitle: $this->servertitle,
            numplayers: $this->numplayers,
            maxplayers: $this->maxplayers,
            players: $this->players,
            errstr: $this->errstr,
        );
    }

    /**
     * Returns the protocol name for Ventrilo.
     *
     * @return string The protocol identifier 'ventrilo'
     */
    #[Override]
    public function getProtocolName(): string
    {
        return $this->protocol;
    }

    /**
     * Extracts the Ventrilo server version from server information.
     *
     * @param ServerInfo $info The server information object
     *
     * @return string The server version string, or 'unknown' if not available
     */
    #[Override]
    public function getVersion(ServerInfo $info): string
    {
        return $info->gameversion ?? 'unknown';
    }

    /**
     * Process and decrypt raw Ventrilo packets (array of binary strings)
     * and return parsed associative array.
     *
     * @param array<mixed> $packets
     *
     * @throws RuntimeException
     *
     * @return array<mixed>
     */
    protected function processPackets(array $packets): array
    {
        // decrypt packets into one string
        $decrypted = $this->decryptPackets($packets);

        // convert %HEX sequences
        $decrypted = preg_replace_callback(
            '|%([0-9A-F]{2})|i',
            static fn (array $matches): string => pack('H*', $matches[1]),
            $decrypted,
        ) ?? $decrypted;

        $lines = preg_split('/\r?\n/', $decrypted);

        if ($lines === false) {
            $lines = [];
        }

        $result  = [];
        $players = [];
        $teams   = [];

        $channelFields = 5;
        $playerFields  = 7;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $colonPos = strpos($line, ':');

            if ($colonPos === false) {
                continue;
            }

            if ($colonPos <= 0) {
                continue;
            }

            $key   = strtolower(substr($line, 0, $colonPos));
            $value = trim(substr($line, $colonPos + 1));

            switch ($key) {
                case 'client':
                    $items = explode(',', $value, $playerFields);
                    $p     = [];

                    foreach ($items as $item) {
                        $parts = array_pad(explode('=', $item, 2), 2, '');
                        $k     = strtolower((string) $parts[0]);
                        $v     = $parts[1] ?? '';
                        $p[$k] = $v;
                    }
                    $players[] = $p;

                    break;

                case 'channel':
                    $items = explode(',', $value, $channelFields);
                    $c     = [];

                    foreach ($items as $item) {
                        $parts = array_pad(explode('=', $item, 2), 2, '');
                        $k     = strtolower((string) $parts[0]);
                        $v     = $parts[1] ?? '';
                        $c[$k] = $v;
                    }
                    $teams[] = $c;

                    break;

                case 'channelfields':
                    $channelFields = count(explode(',', $value));

                    break;

                case 'clientfields':
                    $playerFields = count(explode(',', $value));

                    break;

                default:
                    $result[$key] = $value;

                    break;
            }
        }

        if ($players !== []) {
            $result['players'] = $players;
        }

        if ($teams !== []) {
            $result['teams'] = $teams;
        }

        return $result;
    }

    /**
     * Decrypt Ventrilo header/data packets and return combined plaintext string.
     *
     * @param array<mixed> $packets
     *
     * @throws RuntimeException
     */
    protected function decryptPackets(array $packets): string
    {
        $head_encrypt_table = [
            0x80, 0xE5, 0x0E, 0x38, 0xBA, 0x63, 0x4C, 0x99, 0x88, 0x63, 0x4C, 0xD6, 0x54, 0xB8, 0x65, 0x7E,
            0xBF, 0x8A, 0xF0, 0x17, 0x8A, 0xAA, 0x4D, 0x0F, 0xB7, 0x23, 0x27, 0xF6, 0xEB, 0x12, 0xF8, 0xEA,
            0x17, 0xB7, 0xCF, 0x52, 0x57, 0xCB, 0x51, 0xCF, 0x1B, 0x14, 0xFD, 0x6F, 0x84, 0x38, 0xB5, 0x24,
            0x11, 0xCF, 0x7A, 0x75, 0x7A, 0xBB, 0x78, 0x74, 0xDC, 0xBC, 0x42, 0xF0, 0x17, 0x3F, 0x5E, 0xEB,
            0x74, 0x77, 0x04, 0x4E, 0x8C, 0xAF, 0x23, 0xDC, 0x65, 0xDF, 0xA5, 0x65, 0xDD, 0x7D, 0xF4, 0x3C,
            0x4C, 0x95, 0xBD, 0xEB, 0x65, 0x1C, 0xF4, 0x24, 0x5D, 0x82, 0x18, 0xFB, 0x50, 0x86, 0xB8, 0x53,
            0xE0, 0x4E, 0x36, 0x96, 0x1F, 0xB7, 0xCB, 0xAA, 0xAF, 0xEA, 0xCB, 0x20, 0x27, 0x30, 0x2A, 0xAE,
            0xB9, 0x07, 0x40, 0xDF, 0x12, 0x75, 0xC9, 0x09, 0x82, 0x9C, 0x30, 0x80, 0x5D, 0x8F, 0x0D, 0x09,
            0xA1, 0x64, 0xEC, 0x91, 0xD8, 0x8A, 0x50, 0x1F, 0x40, 0x5D, 0xF7, 0x08, 0x2A, 0xF8, 0x60, 0x62,
            0xA0, 0x4A, 0x8B, 0xBA, 0x4A, 0x6D, 0x00, 0x0A, 0x93, 0x32, 0x12, 0xE5, 0x07, 0x01, 0x65, 0xF5,
            0xFF, 0xE0, 0xAE, 0xA7, 0x81, 0xD1, 0xBA, 0x25, 0x62, 0x61, 0xB2, 0x85, 0xAD, 0x7E, 0x9D, 0x3F,
            0x49, 0x89, 0x26, 0xE5, 0xD5, 0xAC, 0x9F, 0x0E, 0xD7, 0x6E, 0x47, 0x94, 0x16, 0x84, 0xC8, 0xFF,
            0x44, 0xEA, 0x04, 0x40, 0xE0, 0x33, 0x11, 0xA3, 0x5B, 0x1E, 0x82, 0xFF, 0x7A, 0x69, 0xE9, 0x2F,
            0xFB, 0xEA, 0x9A, 0xC6, 0x7B, 0xDB, 0xB1, 0xFF, 0x97, 0x76, 0x56, 0xF3, 0x52, 0xC2, 0x3F, 0x0F,
            0xB6, 0xAC, 0x77, 0xC4, 0xBF, 0x59, 0x5E, 0x80, 0x74, 0xBB, 0xF2, 0xDE, 0x57, 0x62, 0x4C, 0x1A,
            0xFF, 0x95, 0x6D, 0xC7, 0x04, 0xA2, 0x3B, 0xC4, 0x1B, 0x72, 0xC7, 0x6C, 0x82, 0x60, 0xD1, 0x0D,
        ];

        $data_encrypt_table = [
            0x82, 0x8B, 0x7F, 0x68, 0x90, 0xE0, 0x44, 0x09, 0x19, 0x3B, 0x8E, 0x5F, 0xC2, 0x82, 0x38, 0x23,
            0x6D, 0xDB, 0x62, 0x49, 0x52, 0x6E, 0x21, 0xDF, 0x51, 0x6C, 0x6C, 0x76, 0x37, 0x86, 0x50, 0x7D,
            0x48, 0x1F, 0x65, 0xE7, 0x52, 0x6A, 0x88, 0xAA, 0xC1, 0x32, 0x2F, 0xF7, 0x54, 0x4C, 0xAA, 0x6D,
            0x7E, 0x6D, 0xA9, 0x8C, 0x0D, 0x3F, 0xFF, 0x6C, 0x09, 0xB3, 0xA5, 0xAF, 0xDF, 0x98, 0x02, 0xB4,
            0xBE, 0x6D, 0x69, 0x0D, 0x42, 0x73, 0xE4, 0x34, 0x50, 0x07, 0x30, 0x79, 0x41, 0x2F, 0x08, 0x3F,
            0x42, 0x73, 0xA7, 0x68, 0xFA, 0xEE, 0x88, 0x0E, 0x6E, 0xA4, 0x70, 0x74, 0x22, 0x16, 0xAE, 0x3C,
            0x81, 0x14, 0xA1, 0xDA, 0x7F, 0xD3, 0x7C, 0x48, 0x7D, 0x3F, 0x46, 0xFB, 0x6D, 0x92, 0x25, 0x17,
            0x36, 0x26, 0xDB, 0xDF, 0x5A, 0x87, 0x91, 0x6F, 0xD6, 0xCD, 0xD4, 0xAD, 0x4A, 0x29, 0xDD, 0x7D,
            0x59, 0xBD, 0x15, 0x34, 0x53, 0xB1, 0xD8, 0x50, 0x11, 0x83, 0x79, 0x66, 0x21, 0x9E, 0x87, 0x5B,
            0x24, 0x2F, 0x4F, 0xD7, 0x73, 0x34, 0xA2, 0xF7, 0x09, 0xD5, 0xD9, 0x42, 0x9D, 0xF8, 0x15, 0xDF,
            0x0E, 0x10, 0xCC, 0x05, 0x04, 0x35, 0x81, 0xB2, 0xD5, 0x7A, 0xD2, 0xA0, 0xA5, 0x7B, 0xB8, 0x75,
            0xD2, 0x35, 0x0B, 0x39, 0x8F, 0x1B, 0x44, 0x0E, 0xCE, 0x66, 0x87, 0x1B, 0x64, 0xAC, 0xE1, 0xCA,
            0x67, 0xB4, 0xCE, 0x33, 0xDB, 0x89, 0xFE, 0xD8, 0x8E, 0xCD, 0x58, 0x92, 0x41, 0x50, 0x40, 0xCB,
            0x08, 0xE1, 0x15, 0xEE, 0xF4, 0x64, 0xFE, 0x1C, 0xEE, 0x25, 0xE7, 0x21, 0xE6, 0x6C, 0xC6, 0xA6,
            0x2E, 0x52, 0x23, 0xA7, 0x20, 0xD2, 0xD7, 0x28, 0x07, 0x23, 0x14, 0x24, 0x3D, 0x45, 0xA5, 0xC7,
            0x90, 0xDB, 0x77, 0xDD, 0xEA, 0x38, 0x59, 0x89, 0x32, 0xBC, 0x00, 0x3A, 0x6D, 0x61, 0x4E, 0xDB,
            0x29,
        ];

        // Decrypt each packet's header and data
        $decryptedParts = [];

        foreach ($packets as $packet) {
            if (strlen($packet) < 20) {
                throw new RuntimeException('Ventrilo packet too short');
            }

            $header = substr($packet, 0, 20);

            // unpack first 2 bytes as unsigned short (n)
            $tmp = @unpack('n', $header);

            if (!is_array($tmp) || !isset($tmp[1])) {
                throw new RuntimeException('Invalid header unpack');
            }
            $u = (int) $tmp[1];

            $unpacked = @unpack('C*', substr($header, 2));
            $chars    = is_array($unpacked) ? $unpacked : [];

            $a1 = $u & 0xFF;
            $a2 = $u >> 8;

            if ($a1 === 0) {
                throw new RuntimeException('Header key invalid');
            }

            $header_items   = [];
            $table          = $head_encrypt_table;
            $characterCount = count($chars);
            $key            = 0;

            for ($index = 1; $index <= $characterCount; $index++) {
                $chars[$index] = (($chars[$index] ?? 0) - (($table[$a2] ?? 0) + (($index - 1) % 5))) & 0xFF;
                $a2            = ($a2 + $a1) & 0xFF;

                if (($index % 2) === 0) {
                    $b1          = $chars[$index - 1] ?? 0;
                    $b2          = $chars[$index] ?? 0;
                    $packed      = chr($b1) . chr($b2);
                    $short_array = @unpack('n', $packed);

                    if (is_array($short_array) && isset($short_array[1])) {
                        $header_items[$key] = (int) $short_array[1];
                        $key++;
                    }
                }
            }

            $keys = ['zero', 'cmd', 'id', 'totlen', 'len', 'totpck', 'pck', 'datakey', 'crc'];

            $header_assoc = array_combine($keys, $header_items);

            // decrypt data
            $table = $data_encrypt_table;

            if (!isset($header_assoc['datakey']) || !isset($header_assoc['pck'])) {
                throw new RuntimeException('Header missing datakey or pck');
            }

            $a1 = (int) $header_assoc['datakey'] & 0xFF;
            $a2 = (int) $header_assoc['datakey'] >> 8;

            if ($a1 === 0) {
                throw new RuntimeException('Data key invalid');
            }

            $charsDataUnpacked = @unpack('C*', substr($packet, 20));
            $charsData         = is_array($charsDataUnpacked) ? $charsDataUnpacked : [];
            $data              = '';
            $characterCount    = count($charsData);

            for ($index = 1; $index <= $characterCount; $index++) {
                $byte              = ($charsData[$index] ?? 0);
                $byte              = ($byte - (($table[$a2] ?? 0) + (($index - 1) % 72))) & 0xFF;
                $charsData[$index] = $byte;
                $a2                = ($a2 + $a1) & 0xFF;
                $data .= chr($byte);
            }

            $decryptedParts[(int) $header_assoc['pck']] = $data;
        }

        ksort($decryptedParts);

        return implode('', $decryptedParts);
    }
}
