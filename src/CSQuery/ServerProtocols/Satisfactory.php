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
use function pack;
use function strlen;
use function substr;
use function time;
use function unpack;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Queries Satisfactory game servers.
 *
 * Retrieves server information for the game Satisfactory, including player count,
 * server settings, and game state by communicating with the game's query protocol.
 * Enables monitoring of Satisfactory multiplayer servers.
 */
class Satisfactory extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Satisfactory';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Satisfactory'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Satisfactory';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Satisfactory'];

    /**
     * Default query port.
     */
    protected int $port_diff = 0; // Query port is 15777, game port 7777

    /**
     * Constructor.
     *
     * Initializes the Satisfactory query instance.
     *
     * @param null|string $address   The server address to query
     * @param null|int    $queryport The query port for the Satisfactory server (default 15777)
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct();
        $this->address   = $address;
        $this->queryport = $queryport ?? 15777; // Default query port
    }

    /**
     * Queries the Satisfactory server and populates server information.
     *
     * Sends a query request and processes the response to extract server details,
     * player information, and game settings.
     *
     * @param bool $getPlayers Whether to retrieve player information
     * @param bool $getRules   Whether to retrieve server rules/settings
     *
     * @return bool True on successful query, false on failure
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        if ($this->online) {
            $this->reset();
        }

        // Create Poll Server State message
        // ProtocolMagic: 0xF6D5 (little endian)
        // MessageType: 0 (Poll Server State)
        // ProtocolVersion: 1
        // Payload: uint64 LE Cookie (use current time or random)
        // Terminator: 0x1

        $cookie  = time(); // Use current timestamp as cookie
        $payload = pack('VV', $cookie & 0xFFFFFFFF, ($cookie >> 32) & 0xFFFFFFFF);

        $message = pack('v', 0xF6D5) . // ProtocolMagic LE
                   pack('C', 0) .     // MessageType
                   pack('C', 1) .     // ProtocolVersion
                   $payload .         // Cookie
                   pack('C', 0x1);    // Terminator

        $address = (string) $this->address;
        $port    = (int) $this->queryport;

        if (($result = $this->sendCommand($address, $port, $message)) === '' || ($result = $this->sendCommand($address, $port, $message)) === '0' || ($result = $this->sendCommand($address, $port, $message)) === false) {
            $this->errstr = 'No reply received';

            return false;
        }

        // Parse the response
        if (!$this->parseResponse($result)) {
            return false;
        }

        $this->online = true;

        return true;
    }

    // ProtocolInterface methods
    /**
     * query method.
     *
     * Performs a query on the specified Satisfactory server address.
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
        $this->query_server();

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
     * Returns the protocol name for Satisfactory.
     *
     * @return string The protocol identifier 'Satisfactory'
     */
    #[Override]
    public function getProtocolName(): string
    {
        return $this->protocol;
    }

    /**
     * Extracts the Satisfactory server version from server information.
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
     * Parses the raw response data from the Satisfactory server.
     *
     * Extracts server information, player data, and settings from the binary response.
     *
     * @param string $data The raw binary data received from the server
     *
     * @return bool True if parsing was successful, false otherwise
     */
    protected function parseResponse(string $data): bool
    {
        if (strlen($data) < 5) {
            $this->errstr = 'Response too short';

            return false;
        }

        // Check ProtocolMagic
        $tmp = @unpack('v', substr($data, 0, 2));

        if (!is_array($tmp) || !isset($tmp[1])) {
            $this->errstr = 'Invalid response unpack';

            return false;
        }
        $magic = $tmp[1];

        if ($magic !== 0xF6D5) {
            $this->errstr = 'Invalid protocol magic';

            return false;
        }

        $tmp = @unpack('C', substr($data, 2, 1));

        if (!is_array($tmp) || !isset($tmp[1])) {
            $this->errstr = 'Invalid response unpack';

            return false;
        }
        $messageType = $tmp[1];

        if ($messageType !== 1) { // Server State Response
            $this->errstr = 'Unexpected message type';

            return false;
        }

        $tmp = @unpack('C', substr($data, 3, 1));

        if (!is_array($tmp) || !isset($tmp[1])) {
            $this->errstr = 'Invalid response unpack';

            return false;
        }
        $protocolVersion = $tmp[1];

        if ($protocolVersion !== 1) {
            $this->errstr = 'Unsupported protocol version';

            return false;
        }

        $offset = 4;
        $offset += 8;

        // ServerState (uint8)
        $tmp = @unpack('C', substr($data, $offset, 1));

        if (!is_array($tmp) || !isset($tmp[1])) {
            $this->errstr = 'Invalid response unpack';

            return false;
        }
        $serverState = $tmp[1];
        $offset++;

        // ServerNetCL (uint32 LE)
        $tmp = @unpack('V', substr($data, $offset, 4));

        if (!is_array($tmp) || !isset($tmp[1])) {
            $this->errstr = 'Invalid response unpack';

            return false;
        }
        $serverNetCL = $tmp[1];
        $offset += 4;
        $offset += 8;

        // NumSubStates (uint8)
        $tmp = @unpack('C', substr($data, $offset, 1));

        if (!is_array($tmp) || !isset($tmp[1])) {
            $this->errstr = 'Invalid response unpack';

            return false;
        }
        $numSubStates = $tmp[1];
        $offset++;

        // SubStates (array of ServerSubState)
        for ($i = 0; $i < $numSubStates; $i++) {
            // SubStateId (uint8)
            $tmp = @unpack('C', substr($data, $offset, 1));

            if (!is_array($tmp) || !isset($tmp[1])) {
                $this->errstr = 'Invalid response unpack (substate id)';

                return false;
            }
            $subStateId = $tmp[1];
            $offset++;

            // SubStateVersion (uint16 LE)
            $tmp = @unpack('v', substr($data, $offset, 2));

            if (!is_array($tmp) || !isset($tmp[1])) {
                $this->errstr = 'Invalid response unpack (substate version)';

                return false;
            }
            $subStateVersion = $tmp[1];
            $offset += 2;
        }

        // ServerNameLength (uint16 LE)
        $tmp = @unpack('v', substr($data, $offset, 2));

        if (!is_array($tmp) || !isset($tmp[1])) {
            $this->errstr = 'Invalid response unpack';

            return false;
        }
        $serverNameLength = $tmp[1];
        $offset += 2;

        // ServerName (UTF-8)
        $serverName = substr($data, $offset, $serverNameLength);
        $offset += $serverNameLength;

        // Terminator
        if ($offset < strlen($data)) {
            $tmp = @unpack('C', substr($data, $offset, 1));

            if (!is_array($tmp) || !isset($tmp[1])) {
                $this->errstr = 'Invalid response unpack (terminator)';

                return false;
            }
            $terminator = $tmp[1];

            if ($terminator !== 0x1) {
                $this->errstr = 'Invalid terminator';

                return false;
            }
        }

        // Now set the properties
        $this->servertitle = $serverName;
        $this->gamename    = 'Satisfactory';
        $this->gameversion = (string) $serverNetCL; // Use changelist as version

        // Map server state to online status
        // 0: Offline, 1: Idle, 2: Loading, 3: Playing
        if ($serverState === 3 || $serverState === 1) {
            $this->online = true;
        } else {
            $this->online = false;
        }

        // For now, set some defaults since the lightweight API doesn't provide player count etc.
        $this->numplayers = 0; // Not provided in lightweight API
        $this->maxplayers = 0; // Not provided
        $this->mapname    = ''; // Not provided
        $this->gametype   = ''; // Not provided
        $this->password   = -1; // Unknown

        return true;
    }
}
