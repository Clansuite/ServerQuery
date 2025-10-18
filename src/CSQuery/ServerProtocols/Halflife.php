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

use function array_key_exists;
use function array_map;
use function array_values;
use function assert;
use function chr;
use function count;
use function date;
use function in_array;
use function is_array;
use function is_int;
use function is_numeric;
use function microtime;
use function mktime;
use function preg_match;
use function round;
use function strlen;
use function strpos;
use function substr;
use function unpack;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Queries a halflife server.
 *
 * This class works with Halflife only.
 */
class Halflife extends CSQuery
{
    /**
     * Protocol name.
     */
    public string $name = 'Half-Life';

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Halflife';

    /**
     * Game series.
     */
    public array $game_series_list = ['Half-Life'];

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Half-Life'];
    public string $playerFormat  = '/sscore/x2/ftime';

    /**
     * Initializes the Halflife protocol instance with server address and query port.
     *
     * @param string $address   Server IP address or hostname
     * @param int    $queryport Query port number
     */
    public function __construct(string $address, int $queryport)
    {
        parent::__construct();
        $this->address   = $address;
        $this->queryport = $queryport;
    }

    /**
     * Sends an RCON command to the server and returns the response.
     *
     * @param string $command  The RCON command to execute
     * @param string $rcon_pwd The RCON password
     *
     * @return false|string The command response or false on failure
     */
    public function rcon_query_server(string $command, string $rcon_pwd): false|string
    {
        $get_challenge = "\xFF\xFF\xFF\xFFchallenge rcon\n";

        $address   = $this->address ?? '';
        $queryport = $this->queryport ?? 0;

        if (($challenge_rcon = $this->sendCommand($address, $queryport, $get_challenge)) === '' || ($challenge_rcon = $this->sendCommand($address, $queryport, $get_challenge)) === '0' || ($challenge_rcon = $this->sendCommand($address, $queryport, $get_challenge)) === false) {
            $this->debug['Command send ' . $command] = 'No challenge rcon received';

            return false;
        }

        if (in_array(preg_match('/challenge rcon ([0-9]+)/D', $challenge_rcon), [0, false], true)) {
            $this->debug['Command send ' . $command] = 'No valid challenge rcon received';

            return false;
        }
        $challenge_rcon = substr($challenge_rcon, 19, 10);
        $command        = "\xFF\xFF\xFF\xFFrcon \"" . $challenge_rcon . '" ' . $rcon_pwd . ' ' . $command . "\n";

        if (($result = $this->sendCommand($address, $queryport, $command)) === '' || ($result = $this->sendCommand($address, $queryport, $command)) === '0' || ($result = $this->sendCommand($address, $queryport, $command)) === false) {
            $this->debug['Command send ' . $command] = 'No reply received';

            return false;
        }

        return substr($result, 5);
    }

    /**
     * Queries the server for information, optionally including players and rules.
     *
     * @param bool $getPlayers Whether to retrieve the player list
     * @param bool $getRules   Whether to retrieve server rules
     *
     * @return bool True on successful query, false on failure
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        if ($this->online) {
            $this->reset();
        }

        $address   = $this->address ?? '';
        $queryport = $this->queryport ?? 0;

        $starttime = microtime(true);

        // query the basic server info
        $command = "\xFF\xFF\xFF\xFFTSource Engine Query\x00";

        if (($result = $this->sendCommand($address, $queryport, $command)) === '' || ($result = $this->sendCommand($address, $queryport, $command)) === '0' || ($result = $this->sendCommand($address, $queryport, $command)) === false) {
            print '_sendCommand Problem while query_server halflife';

            return false;
        }

        $endtime = microtime(true);
        $diff    = round(($endtime - $starttime) * 1000, 0);
        // response time
        $this->response = round($diff, 2);

        // unlike the other protocols implemented in this class the return value here
        // is a defined structure.  Because php can't handle structures unpack the string
        // into an array and step through the elements reading a bytes as required

        // Unpack used as follows...
        // I = 4 byte long
        // c = 1 byte
        // Format is always a long of -1 [header] followed by a byte [indicator] as validated
        // From that point on array elements are 1 based numeric values

        $data = unpack('Iheader/cindicator/c*', $result);

        if ($data === false) {
            return false;
        }

        assert(isset($data['header'], $data['indicator']));

        /** @var array<int, int> $data */
        if (($data['header'] ?? null) !== -1) {
            $this->debug[$command] = 'Not a hl server, expected 0xFF 0xFF 0xFF 0xFF in first 4 bytes';

            return false;
        }

        if (!isset($data['indicator']) || $data['indicator'] !== 0x6D) {
            $this->debug[$command] = 'Not a hl server, expected 0x6D in byte 5';

            return false;
        }

        $pos = 1;

        $gameip = $this->get_string($data, $pos);
        $pos += strlen($gameip) + 1;

        $hostname = $this->get_string($data, $pos);
        $pos += strlen($hostname) + 1;

        $map = $this->get_string($data, $pos);
        $pos += strlen($map) + 1;

        $gametype = $this->get_string($data, $pos);
        $pos += strlen($gametype) + 1;

        $gamedesc = $this->get_string($data, $pos);
        $pos += strlen($gamedesc) + 1;

        $numplayers = isset($data[$pos]) && is_numeric($data[$pos]) ? (int) $data[$pos] : 0;
        $pos++;

        $maxplayers = isset($data[$pos]) && is_numeric($data[$pos]) ? (int) $data[$pos] : 0;
        $pos++;
        $pos++;
        $pos++;
        $pos++;

        $password = isset($data[$pos]) && is_numeric($data[$pos]) ? (int) $data[$pos] : 0;
        $pos++;

        $ismod = isset($data[$pos]) && is_numeric($data[$pos]) ? (int) $data[$pos] : 0;
        $pos++;

        // if this is a mod, get mod specific information
        if ($ismod === 1) {
            $modurlinfo = $this->get_string($data, $pos);
            $pos += strlen($modurlinfo) + 1;

            $modurldownload = $this->get_string($data, $pos);
            $pos += strlen($modurldownload) + 1;

            $unused = $this->get_string($data, $pos);
            $pos += strlen($unused) + 1;

            $modversion = $this->get_long($data, $pos);
            $pos += 4;

            $modsize = $this->get_long($data, $pos);
            $pos += 4;

            $serverside = $data[$pos];
            $pos++;

            $customclientdll = $data[$pos];
            $pos++;
        }
        $pos++;
        $pos++;

        $this->gamename    = $gamedesc;
        $this->gametype    = $gametype;
        $this->hostport    = $this->queryport ?? 0;
        $this->servertitle = $hostname;
        $this->mapname     = $map;
        $this->numplayers  = $numplayers;
        # $this->numplayers;
        $this->maxplayers  = $maxplayers;
        $this->gameversion = '';
        $this->maptitle    = '';
        $this->password    = $password;

        // Before you can query the players and rules you have to get a 4 byte challenge number
        $command = "\xFF\xFF\xFF\xFF\x57";

        if (($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $command)) === '' || ($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $command)) === '0' || ($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $command)) === false) {
            return false;
        }

        $data = unpack('Iheader/cindicator/c4', $result); // Long followed by bytes

        if (($data['header'] ?? null) !== -1) {
            $this->debug[$command] = 'Invalid challenge no reponse, expected 0xFF 0xFF 0xFF 0xFF in first 4 bytes';

            return false;
        }

        if (!isset($data['indicator']) || $data['indicator'] !== 0x41) {
            $this->debug[$command] = 'Invalid challenge no reponse, expected 0x41 in byte 5';

            return false;
        }

        // build a string containing the number to be sent
        $b1 = isset($data[1]) && is_numeric($data[1]) ? (int) $data[1] : 0;
        $b2 = isset($data[2]) && is_numeric($data[2]) ? (int) $data[2] : 0;
        $b3 = isset($data[3]) && is_numeric($data[3]) ? (int) $data[3] : 0;
        $b4 = isset($data[4]) && is_numeric($data[4]) ? (int) $data[4] : 0;

        $challengeno = chr($b1) . chr($b2) . chr($b3) . chr($b4);

        // get players
        if ($this->numplayers > 0 && $getPlayers) {
            $command = "\xFF\xFF\xFF\xFF\x55" . $challengeno;

            if (($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $command)) === '' || ($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $command)) === '0' || ($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $command)) === false) {
                return false;
            }

            $data = unpack('Iheader/cindicator/cnumplayers/c*', $result);

            if (($data['header'] ?? null) !== -1) {
                $this->debug[$command] = 'Invlaid player reponse, expected 0xFF 0xFF 0xFF 0xFF in first 4 bytes';

                return false;
            }

            if (!isset($data['indicator']) || $data['indicator'] !== 0x44) {
                $this->debug[$command] = 'Invlaid player reponse, expected 0x44 in byte 5';

                return false;
            }

            $numplayers = isset($data['numplayers']) ? (int) $data['numplayers'] : 0;

            $pos = 1;

            $players = [];

            for ($i = 0; $i < $numplayers; $i++) {
                $index = isset($data[$pos]) ? (int) $data[$pos] : 0;
                $pos++;

                $players[$index]['name'] = $this->get_string($data, $pos);
                $pos += strlen($players[$index]['name']) + 1;

                $players[$index]['score'] = $this->get_long($data, $pos);
                $pos += 4;

                // Todo: Get time connected from next 4 bytes as double
                $pos += 4;
            }

            $this->playerkeys['name']  = true;
            $this->playerkeys['score'] = true;

            // normalize players to a sequential array of arrays to match property type
            /** @phpstan-ignore typeCoverage.paramTypeCoverage */
            $this->players = array_values(array_map(static function ($p)
            {
                return $p;
            }, $players));
        }

        // get the server rules
        $command = "\xFF\xFF\xFF\xFF\x56" . $challengeno;

        if (($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $command)) === '' || ($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $command)) === '0' || ($result = $this->sendCommand((string) $this->address, (int) $this->queryport, $command)) === false) {
            return false;
        }

        // This seems to start with a long of -2 then 4 more bytes (don't know what they are), then a byte of 2.
        // The same 9 bytes are repated at offset 1400, so remove them both
        // I assume this is some kind of packet check, if anyone can explain to me, please do - BH
        $offset    = 0;
        $newresult = '';

        while ($offset < strlen($result)) {
            $newresult = $newresult . substr($result, $offset + 9, 1391);
            $offset += 1400;
        }
        $result = $newresult;

        // unpack string now that it is formatted as expected
        // s = 2 byte integer
        $data = unpack('Iheader/cindicator/snumrules/c*', $result);

        if (($data['header'] ?? null) !== -1) {
            $this->debug[$command] = 'Invlaid rules reponse, expected 0xFF 0xFF 0xFF 0xFF in first 4 bytes';

            return false;
        }

        if (!isset($data['indicator']) || $data['indicator'] !== 0x45) {
            $this->debug[$command] = 'Invlaid rules reponse, expected 0x45 in byte 5';

            return false;
        }

        $numrules = isset($data['numrules']) ? (int) $data['numrules'] : 0;

        $pos = 1;

        for ($i = 1; $i < $numrules; $i++) {
            $rulename = $this->get_string($data, $pos);
            $pos += strlen($rulename) + 1;

            $rulevalue = $this->get_string($data, $pos);
            $pos += strlen($rulevalue) + 1;

            $this->rules[$rulename] = $rulevalue;
        }

        return true;
    }

    /**
     * _processPlayers method.
     *
     * @psalm-param 8 $formatLength
     */
    public function processPlayers(string $data, string $format, int $formatLength): bool
    {
        $len = strlen($data);

        for ($i = 6; $i < $len; $i = $endPlayerName + $formatLength + 1) {
            // finding end of player name
            $endPlayerName = strpos($data, "\x00", ++$i);

            if ($endPlayerName === false) {
                return false;
            } // abort on bogus data
            // unpacking player's score and time
            $curPlayer = unpack('@' . ($endPlayerName + 1) . $format, $data);

            if ($curPlayer === false) {
                continue;
            }

            // format time
            if (array_key_exists('time', $curPlayer) && is_int($curPlayer['time'])) {
                $timestamp = mktime(0, 0, $curPlayer['time']);

                if ($timestamp !== false) {
                    $curPlayer['time'] = date('H:i:s', $timestamp);
                }
            }
            // extract player name
            $curPlayer['name'] = substr($data, $i, $endPlayerName - $i);
            // add player to the list of players
            $this->players[] = $curPlayer;
        }

        return true;
    }

    // from an array of bytes keep reading as string until 0x00 terminator
    /**
     * _get_string method.
     *
     * @param array<mixed>|false $data
     *
     * @psalm-param int<1, max> $pos
     */
    public function get_string(mixed $data, int $pos): string
    {
        $string = '';

        if (!is_array($data)) {
            return '';
        }

        $len = count($data);

        while ($pos < $len && isset($data[$pos]) && $data[$pos] !== 0) {
            $string .= chr((int) $data[$pos]);
            $pos++;
        }

        return $string;
    }

    // from an array of bytes, take 4 bytes starting at $pos and convert to little endian long
    /**
     * _get_long method.
     *
     * @param array<mixed>|false $data
     *
     * @psalm-param int<3, max> $pos
     */
    public function get_long(mixed $data, int $pos): int
    {
        if (!is_array($data) || $pos + 3 >= count($data)) {
            return 0;
        }

        $long = (int) ($data[$pos] ?? 0);

        for ($i = 1; $i < 4; $i++) {
            $pos++;
            $long += ((int) ($data[$pos] ?? 0)) << (8 * $i);
        }

        return $long;
    }
}
