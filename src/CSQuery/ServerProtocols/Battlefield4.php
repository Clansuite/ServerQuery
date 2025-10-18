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

use function array_shift;
use function array_values;
use function chr;
use function count;
use function explode;
use function fclose;
use function fread;
use function fsockopen;
use function fwrite;
use function in_array;
use function is_int;
use function is_numeric;
use function pack;
use function reset;
use function str_contains;
use function stream_set_blocking;
use function stream_set_timeout;
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
 * Battlefield 4 Server Query Class.
 */
class Battlefield4 extends CSQuery implements ProtocolInterface
{
    /**
     * Real game host (may differ from query host).
     */
    public ?string $gameHost = null;

    /**
     * Real game port (may differ from query port).
     */
    public ?int $gamePort = null;

    /**
     * Protocol name.
     */
    public string $name = 'Battlefield 4';

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Battlefield4';

    /**
     * Game series.
     */
    public array $game_series_list = ['Battlefield'];

    /**
     * List of supported games.
     */
    public array $supportedGames = ['Battlefield 4'];
    protected int $port_diff     = 22000;

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct($address, $queryport);
    }

    /**
     * Returns a native join URI for BF4.
     */
    #[Override]
    public function getNativeJoinURI(): string
    {
        return 'bf4://' . $this->address . ':' . $this->hostport;
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

        $queryPort = ($this->queryport ?? 0) + $this->port_diff;

        // Attempt TCP query to client_port + 22000 (BF3/BF4 convention)
        $errno   = 0;
        $errstr  = '';
        $address = $this->address ?? '';
        $fp      = @fsockopen($address, $queryPort, $errno, $errstr, 5);

        if ($fp === false) {
            $this->errstr = 'Unable to open TCP socket to BF4 query port';

            return false;
        }
        stream_set_blocking($fp, true);
        stream_set_timeout($fp, 5);

        // serverInfo
        $info = $this->tcpQuery($fp, ['serverInfo']);

        if ($info === false) {
            fclose($fp);
            $this->errstr = 'No BF4 serverInfo response';

            return false;
        }

        // serverInfo: first element should be 'OK'
        if (count($info) === 0 || array_shift($info) !== 'OK') {
            // not a valid response
            fclose($fp);
            $this->errstr = 'Invalid BF4 serverInfo response';

            return false;
        }

        // parse fields following node-gamedig logic
        $this->servertitle = $info[0] ?? '';
        $this->numplayers  = isset($info[1]) ? (int) $info[1] : 0;
        $this->maxplayers  = isset($info[2]) ? (int) $info[2] : 0;
        $this->gametype    = $info[3] ?? '';
        $this->mapname     = $info[4] ?? '';

        $idx = 5;
        $idx++;
        $idx++;

        $teamCount = isset($info[$idx]) ? (int) $info[$idx] : 0;
        $idx++;
        $this->playerteams = [];

        for ($i = 0; $i < $teamCount; $i++) {
            $tickets             = isset($info[$idx]) ? (float) $info[$idx] : 0.0;
            $this->playerteams[] = ['tickets' => $tickets];
            $idx++;
        }

        $this->rules['targetscore'] = isset($info[$idx]) ? (int) $info[$idx] : 0;
        $idx++;
        $this->rules['status'] = $info[$idx] ?? null;
        $idx++;

        // optional fields (ranked, punkbuster, password, uptime, roundtime)
        if (isset($info[$idx])) {
            $this->rules['isRanked'] = ($info[$idx] === 'true');
        } $idx++;

        if (isset($info[$idx])) {
            $this->rules['punkbuster'] = ($info[$idx] === 'true');
        } $idx++;

        if (isset($info[$idx])) {
            $this->password = ($info[$idx] === 'true') ? 1 : 0;
        } $idx++;

        if (isset($info[$idx])) {
            $this->rules['serveruptime'] = (int) $info[$idx];
        } $idx++;

        if (isset($info[$idx])) {
            $this->rules['roundTime'] = (int) $info[$idx];
        } $idx++;

        // try to read ip:port
        if (isset($info[$idx]) && str_contains((string) $info[$idx], ':')) {
            $this->rules['ip'] = $info[$idx];
            $parts             = explode(':', (string) $info[$idx], 2);
            $host              = $parts[0];
            $port              = $parts[1] ?? '';
            $this->gameHost    = $host;
            $this->gamePort    = (int) $port;
            $idx++;
        }

        // version
        $ver = $this->tcpQuery($fp, ['version']);

        if ($ver !== false && count($ver) >= 2) {
            if (($ver[0] ?? '') === 'OK') {
                $this->gameversion = $ver[1] ?? '';
            }
        }
        // players
        $players = $this->tcpQuery($fp, ['listPlayers', 'all']);

        if ($players !== false && array_shift($players) === 'OK') {
            if (count($players) > 0) {
                $fieldCount = isset($players[0]) ? (int) $players[0] : 0;
                $pos        = 1;
                $fields     = [];

                for ($i = 0; $i < $fieldCount; $i++, $pos++) {
                    $fields[] = $players[$pos] ?? '';
                }
                $pos++;
                $numplayers    = isset($players[$pos - 1]) ? (int) $players[$pos - 1] : 0;
                $this->players = [];

                for ($i = 0; $i < $numplayers; $i++) {
                    $player = [];

                    foreach ($fields as $key) {
                        $val = $players[$pos] ?? null;
                        $pos++;

                        if ($key === 'teamId') {
                            $key = 'team';
                        }

                        if ($key === 'squadId') {
                            $key = 'squad';
                        }

                        // numeric fields -> cast
                        if (in_array($key, ['kills', 'deaths', 'score', 'rank', 'team', 'squad', 'ping', 'type'], true)) {
                            $val = is_numeric($val) ? (int) $val : 0;
                        }

                        // normalize sentinel ping values (65535 and other very large values) to 0
                        if ($key === 'ping') {
                            $ival = (int) $val;

                            if ($ival >= 60000) {
                                $val = 0;
                            } else {
                                $val = $ival;
                            }
                        }

                        $player[$key] = $val;
                    }
                    $this->players[] = $player;
                }
            }
        }
        // rules
        $rules = $this->tcpQuery($fp, ['vars']);

        if ($rules !== false && count($rules) > 0 && array_shift($rules) === 'OK') {
            // parse rules as key-value pairs
            $this->rules = [];

            for ($i = 0; $i < count($rules); $i += 2) {
                if (isset($rules[$i], $rules[$i + 1])) {
                    $key               = $rules[$i];
                    $val               = $rules[$i + 1];
                    $this->rules[$key] = $val;
                }
            }
        }

        $this->online = true;
        fclose($fp);

        return true;
    }

    /**
     * Public helper to parse a captured binary blob (one or more BF packets).
     * Returns structured data: serverInfoParams, serverInfo (mapped), players, rawPackets.
     *
     * @param string $data raw captured binary data
     *
     * @return array<string, mixed>
     */
    public function parseCaptured(string $data): array
    {
        $ptr     = 0;
        $len     = strlen($data);
        $packets = [];

        /** @var array<int, array<int, string>> $packets */
        while ($ptr + 8 <= $len) {
            $unpacked = unpack('V', substr($data, $ptr + 4, 4));

            if ($unpacked === false) {
                break;
            }

            $unpackedValue = array_values($unpacked)[0] ?? 0;
            $length        = is_int($unpackedValue) ? $unpackedValue : 0;
            $totalLength   = $length;

            if ($totalLength <= 0 || ($ptr + $totalLength) > $len) {
                break;
            }

            $packet = substr($data, $ptr, $totalLength);
            $params = $this->decodePacket($packet);

            if ($params !== false) {
                $packets[] = $params;
            }
            $ptr += $totalLength;
        }

        $result = [
            'rawPackets'       => $packets,
            'serverInfoParams' => null,
            'serverInfo'       => [],
            'players'          => [],
            'rules'            => [],
        ];

        if (count($packets) === 0) {
            return $result;
        }

        // serverInfo is usually the first packet
        $si                         = reset($packets);
        $result['serverInfoParams'] = $si;

        if (count($si) > 0 && ($si[0] ?? '') === 'OK') {
            $info                  = $si;
            $mapped                = [];
            $mapped['servertitle'] = $info[1] ?? '';
            $mapped['numplayers']  = isset($info[2]) ? (int) $info[2] : 0;
            $mapped['maxplayers']  = isset($info[3]) ? (int) $info[3] : 0;
            $mapped['gametype']    = $info[4] ?? '';
            $mapped['mapname']     = $info[5] ?? '';

            $idx                    = 6;
            $mapped['roundsplayed'] = isset($info[$idx]) ? (int) $info[$idx] : 0;
            $idx++;
            $mapped['roundstotal'] = isset($info[$idx]) ? (int) $info[$idx] : 0;
            $idx++;

            $teamCount = isset($info[$idx]) ? (int) $info[$idx] : 0;
            $idx++;
            $mapped['teams'] = [];

            for ($i = 0; $i < $teamCount; $i++) {
                $mapped['teams'][] = isset($info[$idx]) ? (float) $info[$idx] : 0.0;
                $idx++;
            }

            $mapped['targetscore'] = isset($info[$idx]) ? (int) $info[$idx] : 0;
            $idx++;
            $mapped['status'] = $info[$idx] ?? null;
            $idx++;

            // optional flags
            if (isset($info[$idx])) {
                $mapped['ranked'] = ($info[$idx] === 'true');
            } $idx++;

            if (isset($info[$idx])) {
                $mapped['punkbuster'] = ($info[$idx] === 'true');
            } $idx++;

            if (isset($info[$idx])) {
                $mapped['password'] = ($info[$idx] === 'true');
            } $idx++;

            if (isset($info[$idx])) {
                $mapped['uptime'] = (int) $info[$idx];
            } $idx++;

            if (isset($info[$idx])) {
                $mapped['roundtime'] = (int) $info[$idx];
            } $idx++;

            if (isset($info[$idx]) && str_contains((string) $info[$idx], ':')) {
                $mapped['gameIpAndPort'] = $info[$idx];
            }

            $result['serverInfo'] = $mapped;
        }

        // Find players packet (look for a packet that starts with OK and appears to contain fields)
        foreach ($packets as $p) {
            if (count($p) === 0) {
                continue;
            }

            if (($p[0] ?? '') !== 'OK') {
                continue;
            }

            // heuristic: if packet length indicates fieldCount and fields follow
            $pos = 1;

            if (!isset($p[$pos])) {
                continue;
            }
            $fieldCount = (int) $p[$pos];

            if ($fieldCount <= 0) {
                continue;
            }

            $pos++;
            $fields = [];

            for ($i = 0; $i < $fieldCount; $i++, $pos++) {
                $fields[] = $p[$pos] ?? '';
            }

            if (!isset($p[$pos])) {
                continue;
            }
            $playerCount = (int) $p[$pos];
            $pos++;

            $players = [];

            for ($i = 0; $i < $playerCount; $i++) {
                $player = [];

                foreach ($fields as $f) {
                    $val = $p[$pos] ?? null;
                    $pos++;

                    if ($f === 'teamId') {
                        $f = 'team';
                    }

                    if ($f === 'squadId') {
                        $f = 'squad';
                    }

                    if (in_array($f, ['kills', 'deaths', 'score', 'rank', 'team', 'squad', 'ping', 'type'], true)) {
                        $val = is_numeric($val) ? (int) $val : 0;
                    }

                    if ($f === 'ping') {
                        $ival = (int) $val;

                        if ($ival >= 60000) {
                            $val = 0;
                        } else {
                            $val = $ival;
                        }
                    }
                    $player[$f] = $val;
                }
                $players[] = $player;
            }

            $result['players'] = $players;

            break;
        }

        return $result;
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
        return 'battlefield4';
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
     * @param array<string> $params
     */
    private function buildPacket(array $params): string
    {
        $parts       = [];
        $totalLength = 12;

        foreach ($params as $p) {
            $b       = (string) $p;
            $parts[] = $b;
            $totalLength += 4 + strlen($b) + 1;
        }

        $out = '';
        // header (0) little-endian
        $out .= pack('V', 0);
        // total length
        $out .= pack('V', $totalLength);
        // param count
        $out .= pack('V', count($params));

        foreach ($parts as $p) {
            $out .= pack('V', strlen($p));
            $out .= $p;
            $out .= chr(0);
        }

        return $out;
    }

    /**
     * @param resource      $fp
     * @param array<string> $params
     *
     * @return array<string>|false
     */
    private function tcpQuery(mixed $fp, array $params): array|false
    {
        $packet  = $this->buildPacket($params);
        $written = fwrite($fp, $packet);

        if ($written === false) {
            return false;
        }

        $buf   = '';
        $start = time();

        while (true) {
            $chunk = fread($fp, 8192);

            if ($chunk === false) {
                break;
            }

            if ($chunk !== '') {
                $buf .= $chunk;
            }

            $decoded = $this->decodePacket($buf);

            if ($decoded === false) {
                // need more data
            } else {
                return $decoded;
            }

            // timeout 2s
            if ((time() - $start) > 2) {
                break;
            }
        }

        return false;
    }

    /**
     * @return array<string>|false
     */
    private function decodePacket(string $buffer): array|false
    {
        if (strlen($buffer) < 8) {
            return false;
        }

        // manual pointer decode (header + totalLength already present)
        $unpacked = unpack('V', substr($buffer, 0, 4));

        if ($unpacked === false) {
            return false;
        }

        /** @var array<int, int> $unpacked */
        $header   = ($unpacked[1] ?? 0);
        $unpacked = unpack('V', substr($buffer, 4, 4));

        if ($unpacked === false) {
            return false;
        }

        /** @var array<int, int> $unpacked */
        $totalLength = ($unpacked[1] ?? 0);

        // ensure we have whole packet
        if (strlen($buffer) < $totalLength) {
            return false;
        }

        // check response flag (0x40000000)
        if ((($header & 0x40000000) === 0)) {
            // not a response packet
            return false;
        }

        $ptr      = 8;
        $unpacked = unpack('V', substr($buffer, $ptr, 4));

        if ($unpacked === false) {
            return false;
        }
        $paramCount = ($unpacked[1] ?? 0);
        $ptr += 4;
        $params = [];

        for ($i = 0; $i < $paramCount; $i++) {
            $unpacked = unpack('V', substr($buffer, $ptr, 4));

            if ($unpacked === false) {
                return false;
            }

            /** @var array<int, int> $unpacked */
            $len = ($unpacked[1] ?? 0);
            $ptr += 4;
            $s = '';

            if ($len > 0) {
                $s = substr($buffer, $ptr, $len);
            }
            $ptr += $len;
            // skip null terminator
            $ptr++;
            $params[] = $s;
        }

        return $params;
    }
}
