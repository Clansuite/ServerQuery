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

namespace Clansuite\ServerQuery;

use function base64_decode;
use function class_exists;
use function count;
use function htmlspecialchars;
use function is_string;
use function json_encode;
use function preg_match;
use function serialize;
use function strcasecmp;
use function strlen;
use function substr;
use function uasort;
use function unserialize;
use Clansuite\ServerQuery\Util\UdpClient;
use InvalidArgumentException;
use RuntimeException;

/**
 * Base class for game server query protocols.
 */
class CSQuery
{
    /** ip or hostname of the server */
    public ?string $address = null;

    /**  port to use for the query */
    public ?int $queryport = null;

    /**  the port you have to connect to enter the game */
    public int $hostport = 0;

    /**
     *  status of the server.
     *
     * TRUE: server online, FALSE: server offline
     */
    public bool $online;

    /**  the name of the game */
    public string $gamename;

    /**  the version of the game */
    public string $gameversion;

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Base';

    /**  The title of the server */
    public string $servertitle;

    /**  The name of the map (often corresponds with the filename of the map)*/
    public string $mapname;

    /**  A more descriptive name of the map */
    public string $maptitle;

    /**  The gametype */
    public string $gametype;

    /**  current number of players on the server */
    public int $numplayers;

    /**  maximum number of players allowed on the server */
    public int $maxplayers;
    public int $steamAppID;

    /**
     *  Wheather the game server is password protected.
     *
     *  1: server is password protected<br>
     *  0: server is not password protected<br>
     * -1: unknown
     */
    public int $password = -1;

    /**  next map on the server */
    public string $nextmap = '';

    /**
     * Players playing on the server.
     *
     * @see playerkeys
     *
     * Hash with player ids as key.
     * The containing value will be another hash with the infos of the player.
     * To access a player name use <code>players[$playerid]['name']</code>.
     * Check playerkeys to get the keys available
     *
     * @var array<array<string, mixed>>
     */
    public array $players = [];

    /**
     * Hash of available player infos.
     *
     * There is a key for each player info available (e.g. name, score, ping etc).
     * The value is TRUE if the info is available
     *
     * @var array<string, bool>
     */
    public array $playerkeys;

    /**  list of the team names.
     * @var array<array<string, mixed>>
     */
    public array $playerteams = [];

    /**  a list of all maps in cycle.
     * @var array<string>
     */
    public array $maplist = [];

    /**
     * Hash with all server rules.
     *
     * key:   rulename<br>
     * value: rulevalue
     *
     * @var array<string, mixed>
     */
    public array $rules = [];

    /**  Short errormessage if something goes wrong */
    public string $errstr = '';

    /**  Response time in milliseconds */
    public float $response = 0.0;
    public string $name    = '';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = [];

    /**
     * List of supported game series.
     *
     * @var array<string>
     */
    public array $game_series_list = [];

    /**
     *  Array with debug infos.
     *
     * Stores all the send/received data
     * Format: send data => received data
     *
     * @var array<mixed>
     */
    public array $debug;
    protected UdpClient $udpClient;

    /**
     *  Initializes the CSQuery instance.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        $this->address   = $address;
        $this->queryport = $queryport;
        $this->udpClient = new UdpClient;
        // clear publics
        $this->reset();
    }

    /**
     * Returns a list of property names to serialize for object serialization.
     *
     * @return array list of property names to serialize
     */
    public function __sleep()
    {
        // do not serialize debug info to keep the result small
        return [
            'address',
            'queryport',
            'gamename',
            'hostport',
            'online',
            'gameversion',
            'servertitle',
            'mapname',
            'maptitle',
            'gametype',
            'numplayers',
            'maxplayers',
            'password',
            'nextmap',
            'players',
            'playerkeys',
            'playerteams',
            'maplist',
            'rules',
            'errstr',
        ];
    }

    /**
     * Returns a map of supported protocols to their implementing class names.
     *
     * @return array<string, string> An array with names of the supported protocols
     */
    public function getProtocolsMap(): array
    {
        return ServerProtocols::getProtocolsMap();
    }

    /**
     * Return the names of the supported server protocols.
     *
     * @return array<string> An array with names of the supported protocols
     */
    public function getSupportedProtocols(): array
    {
        return ServerProtocols::getSupportedProtocols();
    }

    /**
     * Return the class name for a given protocol.
     *
     * @param string $protocolClassname the protocol name
     *
     * @return string the class name for the protocol
     */
    public function getProtocolClass(string $protocolClassname): mixed
    {
        return ServerProtocols::getProtocolClass($protocolClassname);
    }

    /**
     * Create a new instance of a protocol-specific CSQuery subclass.
     *
     * @param string $protocol the protocol name (e.g. 'Csgo', 'Steam')
     * @param string $address  the server address
     * @param int    $port     the query port
     *
     * @throws InvalidArgumentException if the protocol is not supported
     *
     * @return CSQuery a CSQuery object that supports the specified protocol
     */
    public function createInstance(string $protocol, string $address, int $port): self
    {
        $className = $this->getProtocolClass($protocol);

        /** @var class-string<CSQuery> $className */
        if (!class_exists($className)) {
            throw new InvalidArgumentException("Protocol '{$protocol}' is not supported.");
        }

        return new $className($address, $port);
    }

    /**
     * Set a custom UDP client (for testing with fixtures).
     *
     * @param UdpClient $udpClient the UDP client to set
     */
    public function setUdpClient(UdpClient $udpClient): void
    {
        $this->udpClient = $udpClient;
    }

    /**
     * Use this to restore a object that has been previously serialized with
     * serialize.
     *
     * @param string $string serialized CSQuery object
     *
     * @return mixed the deserialized data
     */
    public function unserialize(string $string): mixed
    {
        // extracting class name
        $length = strlen($string);
        $i      = 0;

        for (; $i < $length; $i++) {
            if ($string[$i] === ':') {
                break;
            }
        }

        $className = substr($string, 0, $i);

        // we should be careful when using eval with supplied arguments
        if (preg_match('/^[A-Za-z0-9_-]+$/D', $className) !== false) {
            // In the new structure, classes are autoloaded via composer
            // include_once is not needed
        }

        $data = base64_decode(substr($string, $i + 1), true);

        if ($data === false) {
            throw new RuntimeException('Invalid base64 data in serialized string');
        }

        return unserialize($data);
    }

    /**
     * Returns a native join URI.
     *
     * Some games are registering an URI type to allow easy joining of games
     *
     * @return false|string a native join URI or false if not implemented for the game
     */
    public function getNativeJoinURI(): false|string
    {
        return false;
    }

    /**
     * Queries the server.
     *
     * @param bool $getPlayers whether to retrieve player infos
     * @param bool $getRules   whether to retrieve rules
     *
     * @return bool true on success
     */
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        $this->errstr = 'This class cannot be used to query a server';

        return false;
    }

    /**
     * Sorts the given players.
     *
     * You can sort by name, score, frags, deaths, honor and time
     *
     * @param array<array<string, mixed>> $players players to sort
     * @param string                      $sortkey sort by the given key
     *
     * @return array<array<string, mixed>> sorted player hash
     */
    public function sortPlayers(array $players, string $sortkey = 'name'): array
    {
        if (count($players) === 0) {
            return [];
        }

        match ($sortkey) {
            'name'   => uasort($players, [$this, 'sortByName']),
            'score'  => uasort($players, [$this, 'sortByScore']),
            'frags'  => uasort($players, [$this, 'sortByFrags']),
            'deaths' => uasort($players, [$this, 'sortByDeaths']),
            'kills'  => uasort($players, [$this, 'sortByKills']),
            'time'   => uasort($players, [$this, 'sortByTime']),
            default  => $players,
        };

        return $players;
    }

    /**
     * Returns the server data as JSON string.
     */
    public function toJson(): false|string
    {
        return json_encode($this);
    }

    /**
     * Returns the server data as HTML string.
     */
    public function toHtml(): string
    {
        $html = '<!DOCTYPE html><html><head><title>Server Info</title></head><body>';
        $html .= '<h1>' . htmlspecialchars($this->servertitle) . '</h1>';
        $html .= '<p>Address: ' . htmlspecialchars($this->address ?? '') . ':' . $this->hostport . '</p>';
        $html .= '<p>Game: ' . htmlspecialchars($this->gamename) . '</p>';
        $html .= '<p>Map: ' . htmlspecialchars($this->mapname) . '</p>';
        $html .= '<p>Players: ' . $this->numplayers . '/' . $this->maxplayers . '</p>';
        $html .= '<p>Online: ' . ($this->online ? 'Yes' : 'No') . '</p>';

        if ($this->players !== []) {
            $html .= '<h2>Players</h2><ul>';

            foreach ($this->players as $player) {
                $name = $player['name'] ?? 'Unknown';

                if (!is_string($name)) {
                    $name = 'Unknown';
                }
                $html .= '<li>' . htmlspecialchars($name) . '</li>';
            }
            $html .= '</ul>';
        }
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Resets the object to its initial state.
     */
    protected function reset(): void
    {
        // Ensure address and query port have sensible defaults so typed properties
        // are initialized and safe to access immediately after construction.
        // Note: address and queryport are set in constructor and should not be reset
        $this->online      = false;
        $this->gamename    = '';
        $this->hostport    = 0;
        $this->gameversion = '';
        $this->servertitle = '';
        $this->mapname     = '';
        $this->maptitle    = '';
        $this->gametype    = '';
        $this->numplayers  = 0;
        $this->maxplayers  = 0;
        $this->password    = -1;
        $this->nextmap     = '';
        $this->players     = [];
        $this->playerkeys  = [];
        $this->playerteams = [];
        $this->maplist     = [];
        $this->rules       = [];
        $this->errstr      = '';
        $this->response    = 0.0;
        $this->debug       = [];
        $this->steamAppID  = 0;
    }

    /**
     * Send a command to the server.
     *
     * @param string $address Server address
     * @param int    $port    Server port
     * @param string $command Command to send
     *
     * @return false|string The response or false on error
     */
    protected function sendCommand(string $address, int $port, string $command): false|string
    {
        $this->debug[] = '-> ' . $command;

        $result = $this->udpClient->query($address, $port, $command);

        if ($result === null) {
            $this->debug[] = '<- (no response)';

            return false;
        }

        $this->debug[] = '<- ' . $result;

        return $result;
    }

    /**
     * Sorting helper for player names.
     *
     * @param array<string, mixed> $a first player
     * @param array<string, mixed> $b second player
     *
     * @return int comparison result
     */
    private function sortByName(array $a, array $b): int
    {
        $nameA = $a['name'] ?? '';
        $nameA = is_string($nameA) ? $nameA : '';
        $nameB = $b['name'] ?? '';
        $nameB = is_string($nameB) ? $nameB : '';

        return strcasecmp($nameA, $nameB);
    }

    /**
     * Sorting helper for player scores.
     *
     * @param array<string, mixed> $a first player
     * @param array<string, mixed> $b second player
     *
     * @return int comparison result
     */
    private function sortByScore(array $a, array $b): int
    {
        $scoreA = $a['score'] ?? 0;
        $scoreB = $b['score'] ?? 0;

        if ($scoreA === $scoreB) {
            return 0;
        }

        if ($scoreA < $scoreB) {
            return 1;
        }

        return -1;
    }

    /**
     * Sorting helper for player frags.
     *
     * @param array<string, mixed> $a first player
     * @param array<string, mixed> $b second player
     *
     * @return int comparison result
     */
    private function sortByFrags(array $a, array $b): int
    {
        $fragsA = $a['frags'] ?? 0;
        $fragsB = $b['frags'] ?? 0;

        if ($fragsA === $fragsB) {
            return 0;
        }

        if ($fragsA < $fragsB) {
            return 1;
        }

        return -1;
    }

    /**
     * Sorting helper for player deaths.
     *
     * @param array<string, mixed> $a first player
     * @param array<string, mixed> $b second player
     *
     * @return int comparison result
     */
    private function sortByDeaths(array $a, array $b): int
    {
        $deathsA = $a['deaths'] ?? 0;
        $deathsB = $b['deaths'] ?? 0;

        if ($deathsA === $deathsB) {
            return 0;
        }

        if ($deathsA < $deathsB) {
            return 1;
        }

        return -1;
    }

    /**
     * Sorting helper for player time.
     *
     * @param array<string, mixed> $a first player
     * @param array<string, mixed> $b second player
     *
     * @return int comparison result
     */
    private function sortByTime(array $a, array $b): int
    {
        $timeA = $a['time'] ?? 0;
        $timeB = $b['time'] ?? 0;

        if ($timeA === $timeB) {
            return 0;
        }

        if ($timeA < $timeB) {
            return 1;
        }

        return -1;
    }

    /**
     * Sorting helper for player kills.
     *
     * @param array<string, mixed> $a first player
     * @param array<string, mixed> $b second player
     *
     * @return int comparison result
     */
    private function sortByKills(array $a, array $b): int
    {
        $killsA = $a['kills'] ?? 0;
        $killsB = $b['kills'] ?? 0;

        if ($killsA === $killsB) {
            return 0;
        }

        if ($killsA < $killsB) {
            return 1;
        }

        return -1;
    }
}
