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
use function chr;
use function count;
use function explode;
use function fclose;
use function fread;
use function fsockopen;
use function fwrite;
use function in_array;
use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function pack;
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
 * Battlefield 3 / 4 / Hardline (Frostbite) Server Query Class.
 */
class Bf3 extends CSQuery implements ProtocolInterface
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
    public string $name = 'Battlefield 3';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Battlefield 3', 'Battlefield 4', 'Battlefield: Hardline'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Frostbite';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Battlefield'];
    protected int $port_diff       = 22000;

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct();
        $this->address   = $address ?? '';
        $this->queryport = $queryport ?? 0;
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
        $errno  = 0;
        $errstr = '';
        $address = $this->address ?? '';
        $fp = @fsockopen($address, $queryPort, $errno, $errstr, 5);

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
        if (!isset($info[0]) || $info[0] !== 'OK') {
            // not a valid response
            fclose($fp);
            $this->errstr = 'Invalid BF4 serverInfo response';

            return false;
        }

        // parse fields following node-gamedig logic
        $st                = $info[0] ?? null;
        $this->servertitle = is_string($st) ? $st : '';

        $np = $info[1] ?? null;

        if (is_numeric($np)) {
            $this->numplayers = (int) $np;
        } else {
            $this->numplayers = 0;
        }

        $mp = $info[2] ?? null;

        if (is_numeric($mp)) {
            $this->maxplayers = (int) $mp;
        } else {
            $this->maxplayers = 0;
        }

        $gt             = $info[3] ?? null;
        $this->gametype = is_string($gt) ? $gt : '';

        $mn            = $info[4] ?? null;
        $this->mapname = is_string($mn) ? $mn : '';

        $idx = 5;
        $idx++;
        $idx++;

        if (isset($info[$idx]) && is_numeric($info[$idx])) {
            $teamCount = (int) $info[$idx];
        } else {
            $teamCount = 0;
        }
        $idx++;
        $this->playerteams = [];

        for ($i = 0; $i < $teamCount; $i++) {
            if (isset($info[$idx]) && is_numeric($info[$idx])) {
                $tickets = (float) $info[$idx];
            } else {
                $tickets = 0.0;
            }
            $this->playerteams[] = ['tickets' => $tickets];
            $idx++;
        }

        if (isset($info[$idx]) && is_numeric($info[$idx])) {
            $this->rules['targetscore'] = (int) $info[$idx];
        } else {
            $this->rules['targetscore'] = 0;
        }
        $idx++;
        $this->rules['status'] = isset($info[$idx]) && is_string($info[$idx]) ? $info[$idx] : null;
        $idx++;

        // optional fields (ranked, punkbuster, password, uptime, roundtime)
        if (isset($info[$idx])) {
            $this->rules['isRanked'] = ($info[$idx] === 'true');
        }
        $idx++;

        if (isset($info[$idx])) {
            $this->rules['punkbuster'] = ($info[$idx] === 'true');
        }
        $idx++;

        if (isset($info[$idx])) {
            $this->password = ($info[$idx] === 'true') ? 1 : 0;
        }
        $idx++;

        if (isset($info[$idx])) {
            if (is_numeric($info[$idx])) {
                $this->rules['serveruptime'] = (int) $info[$idx];
            } else {
                $this->rules['serveruptime'] = 0;
            }
        }
        $idx++;

        if (isset($info[$idx])) {
            if (is_numeric($info[$idx])) {
                $this->rules['roundTime'] = (int) $info[$idx];
            } else {
                $this->rules['roundTime'] = 0;
            }
        }
        $idx++;

        // try to read ip:port
        if (isset($info[$idx]) && is_string($info[$idx]) && str_contains($info[$idx], ':')) {
            $this->rules['ip'] = $info[$idx];
            $exploded          = explode(':', $info[$idx]);

            /** @var array{0: string, 1: string} $exploded */
            $host           = $exploded[0];
            $port           = $exploded[1];
            $this->gameHost = $host;
            $this->gamePort = is_numeric($port) ? (int) $port : 0;
            $idx++;
        }

        // version
        $ver = $this->tcpQuery($fp, ['version']);

        if (is_array($ver) && count($ver) >= 2 && isset($ver[0]) && $ver[0] === 'OK') {
            $this->gameversion = isset($ver[1]) && is_string($ver[1]) ? $ver[1] : '';
        }
        // players
        $players = $this->tcpQuery($fp, ['listPlayers', 'all']);

        if (is_array($players) && count($players) > 0 && ($first = array_shift($players)) === 'OK') {
            $fieldCount = isset($players[0]) && is_numeric($players[0]) ? (int) $players[0] : 0;
            $pos        = 1;

            /** @var array<string> $fields */
            $fields = [];

            for ($i = 0; $i < $fieldCount; $i++, $pos++) {
                $fields[] = isset($players[$pos]) && is_string($players[$pos]) ? $players[$pos] : '';
            }
            $pos++;
            $numplayers    = isset($players[$pos - 1]) && is_numeric($players[$pos - 1]) ? (int) $players[$pos - 1] : 0;
            $this->players = [];

            for ($i = 0; $i < $numplayers; $i++) {
                /** @var array<string, mixed> $player */
                $player = [];

                foreach ($fields as $key) {
                    $val = $players[$pos] ?? null;

                    // numeric fields -> cast
                    if (in_array($key, ['kills', 'deaths', 'score', 'rank', 'team', 'squad', 'ping', 'type'], true)) {
                        if (is_numeric($val)) {
                            $val = (int) $val;
                        } else {
                            $val = 0;
                        }
                    }

                    // normalize sentinel ping values (65535 and other very large values) to 0
                    if ($key === 'ping' && is_numeric($val)) {
                        $ival = (int) $val;

                        if ($ival >= 60000) {
                            $val = 0;
                        } else {
                            $val = $ival;
                        }
                    }

                    $player[$key] = $val;
                    $pos++;
                }
                $this->players[] = $player;
            }
        }

        fclose($fp);

        return true;
    }

    /**
     * Public helper to parse a captured binary blob (one or more BF packets).
     * Returns structured data: serverInfoParams, serverInfo (mapped), players, rawPackets.
     *
     * @param string $data raw captured binary data
     *
     * @return array<string,mixed>
     */
    public function parseCaptured(string $data): array
    {
        $ptr     = 0;
        $len     = strlen($data);
        /** @var string[] $packets */
        $packets = [];

        while ($ptr + 8 <= $len) {
            $unpacked = unpack('V', substr($data, $ptr + 4, 4));

            if ($unpacked === false) {
                break;
            }

            /** @var array{1: int} $unpacked */
            $totalLength = $unpacked[1];

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
        /** @var string $si */
        /** @phpstan-ignore-next-line offsetAccess.notFound */
        $si                         = $packets[0];
        $result['serverInfoParams'] = $si;

        if (strlen($si) > 0 && str_starts_with($si, 'OK')) {
            $info                  = explode("\t", $si);
            $mapped                = [];
            $st                    = $info[1] ?? null;
            $mapped['servertitle'] = $st ?? '';

            $np                   = $info[2] ?? null;
            $mapped['numplayers'] = (int) ($np ?? 0);

            $mp                   = $info[3] ?? null;
            $mapped['maxplayers'] = (int) ($mp ?? 0);

            $gt                 = $info[4] ?? null;
            $mapped['gametype'] = $gt ?? '';

            $mn                = $info[5] ?? null;
            $mapped['mapname'] = is_string($mn) ? $mn : '';

            $idx                    = 6;
            $mapped['roundsplayed'] = isset($info[$idx]) && is_numeric($info[$idx]) ? (int) $info[$idx] : 0;
            $idx++;
            $mapped['roundstotal'] = isset($info[$idx]) && is_numeric($info[$idx]) ? (int) $info[$idx] : 0;
            $idx++;

            $teamCount = isset($info[$idx]) && is_numeric($info[$idx]) ? (int) $info[$idx] : 0;
            $idx++;
            $mapped['teams'] = [];

            for ($i = 0; $i < $teamCount; $i++) {
                $mapped['teams'][] = isset($info[$idx]) && is_numeric($info[$idx]) ? (float) $info[$idx] : 0.0;
                $idx++;
            }

            $mapped['targetscore'] = isset($info[$idx]) && is_numeric($info[$idx]) ? (int) $info[$idx] : 0;
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

            if (isset($info[$idx]) && is_numeric($info[$idx])) {
                $mapped['uptime'] = (int) $info[$idx];
            } $idx++;

            if (isset($info[$idx]) && is_numeric($info[$idx])) {
                $mapped['roundtime'] = (int) $info[$idx];
            } $idx++;

            if (isset($info[$idx]) && is_string($info[$idx]) && str_contains($info[$idx], ':')) {
                $mapped['gameIpAndPort'] = $info[$idx];
            }

            $result['serverInfo'] = $mapped;
        }

        // Find players packet (look for a packet that starts with OK and appears to contain fields)
        foreach ($packets as $p) {
            /** @var string $p */
            if (strlen($p) === 0) {
                continue;
            }

            $p = explode("\t", $p);
            if (count($p) === 0) {
                continue;
            }

            if ($p[0] !== 'OK') {
                continue;
            }

            // heuristic: if packet length indicates fieldCount and fields follow
            $pos = 1;

            if (!isset($p[$pos])) {
                continue;
            }
            $fieldCount = (int) $p[$pos];
            $pos++;

            /** @var array<string> $fields */
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
                /** @var array<string, mixed> $player */
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
                        $ival = $val;

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
     * @param array<int,string> $params
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
     * TCP query helper.
     *
     * @param resource          $fp
     * @param array<int,string> $params
     *
     * @return array<mixed>|false
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
     * @return array<string,mixed>|false
     */
    private function decodePacket(string $buffer): array|false
    {
        if (strlen($buffer) < 8) {
            return false;
        }

        // manual pointer decode (header + totalLength already present)
        $headerUnpacked = unpack('V', substr($buffer, 0, 4));

        if ($headerUnpacked === false || !isset($headerUnpacked[1]) || !is_numeric($headerUnpacked[1])) {
            return false;
        }

        $header = (int) $headerUnpacked[1];

        $totalLengthUnpacked = unpack('V', substr($buffer, 4, 4));

        if ($totalLengthUnpacked === false || !isset($totalLengthUnpacked[1]) || !is_numeric($totalLengthUnpacked[1])) {
            return false;
        }

        $totalLength = (int) $totalLengthUnpacked[1];

        // ensure we have whole packet
        if (strlen($buffer) < $totalLength) {
            return false;
        }

        // check response flag (0x40000000)
        if ((($header & 0x40000000) === 0)) {
            // not a response packet
            return false;
        }

        $ptr    = 8;
        $result = [];

        // decode key/value pairs
        for ($i = 0; $i < $header; $i++) {
            if (strlen($buffer) < $ptr + 4) {
                break;
            }
            $keyLenUnpacked = unpack('V', substr($buffer, $ptr, 4));

            if ($keyLenUnpacked === false || !isset($keyLenUnpacked[1]) || !is_numeric($keyLenUnpacked[1])) {
                break;
            }

            $keyLen = (int) $keyLenUnpacked[1];
            $ptr += 4;

            if (strlen($buffer) < $ptr + $keyLen) {
                break;
            }
            $key = substr($buffer, $ptr, $keyLen);
            $ptr += $keyLen;

            if (strlen($buffer) < $ptr + 4) {
                break;
            }
            $valLenUnpacked = unpack('V', substr($buffer, $ptr, 4));

            if ($valLenUnpacked === false || !isset($valLenUnpacked[1]) || !is_numeric($valLenUnpacked[1])) {
                break;
            }

            $valLen = (int) $valLenUnpacked[1];
            $ptr += 4;

            if (strlen($buffer) < $ptr + $valLen) {
                break;
            }
            $val = substr($buffer, $ptr, $valLen);
            $ptr += $valLen;

            $result[$key] = $val;
        }

        return $result;
    }
}
