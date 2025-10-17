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

use const PREG_SPLIT_NO_EMPTY;
use function array_key_exists;
use function chr;
use function count;
use function date;
use function explode;
use function is_array;
use function is_finite;
use function is_numeric;
use function microtime;
use function mktime;
use function ord;
use function preg_match;
use function preg_split;
use function round;
use function strlen;
use function strpos;
use function substr;
use function unpack;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Steam protocol implementation.
 *
 * @see https://developer.valvesoftware.com/wiki/Server_queries
 */
class Steam extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Steam';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = [
        'Steam',
        'Counter-Strike: Global Offensive',
        'Counter-Strike: Source',
        'Team Fortress 2',
        'Left 4 Dead 2',
        'Garry\'s Mod',
        'Half-Life 2: Deathmatch',
        'Day of Defeat: Source',
        'Zombie Panic! Source',
        'Alien Swarm',
        'Black Mesa',
        'Blade Symphony',
        'Ballistic Overkill',
        'Battalion 1944',
        'Barotrauma',
        'Abiotic Factor',
        'Avorion',
        'Atlas',
        'ARMA 2',
        'Age of Chivalry',
        'America\'s Army 3',
        'America\'s Army: Proving Grounds',
        'Aliens vs. Predator 2010',
        'Base Defense',
        'Contagion',
        'Insurgency',
        'Insurgency: Sandstorm',
        'Homefront',
        'Hurtworld',
        'Killing Floor 2',
        'Natural Selection',
        'Monday Night Combat',
        'No More Room in Hell',
        'Nuclear Dawn',
        'Perfect Dark',
        'Portal 2',
        'Rust',
        'Serious Sam 3: BFE',
        'Space Engineers',
        'Squad',
        'The Ship',
        'Unturned',
        'Vampire: The Masquerade - Bloodlines',
        'Warframe',
        'Worms Armageddon',
        'ZPS',
    ];

    /**
     * Protocol identifier.
     */
    public string $protocol     = 'A2S';
    public string $playerFormat = '/sscore/x2/ftime';

    // Response time in milliseconds
    public float $response = 0.0;

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct($address, $queryport);
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

        $starttime = microtime(true);

        $address = (string) $this->address;
        $port    = (int) $this->queryport;

        $command = "\xFF\xFF\xFF\xFF\x54\x53\x6F\x75\x72\x63\x65\x20\x45\x6E\x67\x69\x6E\x65\x20\x51\x75\x65\x72\x79\x00";

        // Try up to three times to get a valid response. Preserve the first
        // non-empty/non-false/non-'0' result so we don't accidentally
        // overwrite a valid reply with a later '(no response)' capture.
        $attempts = 0;
        $result   = false;

        while ($attempts < 3) {
            $attempts++;
            $tmp = $this->sendCommand($address, $port, $command);

            // If we got a non-empty and non-false and non-'0' response, keep it.
            if ($tmp !== '' && $tmp !== false && $tmp !== '0') {
                $result = $tmp;

                break;
            }

            // Otherwise if we got something (like an informative string), keep it
            // only if we don't already have a valid result. This mirrors the
            // previous behaviour but avoids overwriting the first real reply.
            if ($result === false) {
                $result = $tmp;
            }
        }

        if ($result === '' || $result === '0' || $result === false) {
            $this->errstr = 'No response from server';

            return false;
        }

        $endtime = microtime(true);
        $diff    = round(($endtime - $starttime) * 1000, 0);
        // response time
        $this->response = round($diff, 2);

        $this->hostport = $this->queryport ?? 0;

        // Ensure rule keys exist to avoid undefined index notices when appending
        $this->rules['gamedir'] ??= '';
        $this->rules['IP'] = $this->rules['gamedir'];
        $this->rules['gamename'] ??= '';
        $this->rules['mod_url'] ??= '';

        $i   = 4; // start after header
        $len = strlen($result);

        if ($i >= $len) {
            $this->errstr = 'Invalid response (too short)';

            return false;
        }

        // A2S (Source) replies may start with 'I' (0x49) or 'A' (0x41) in practice.
        $firstTypeChar       = $result[$i++] ?? "\0";
        $this->rules['Type'] = ($firstTypeChar === 'I' || $firstTypeChar === 'A') ? 'Source' : 'HL1';

        // If we received an S2C_CHALLENGE ('A') the server returned a 4-byte
        // challenge. Clients should resend the original A2S_INFO request with
        // that 4-byte challenge appended. Some servers reply with the challenge
        // instead of the full info to mitigate reflection attacks.
        if ($firstTypeChar === 'A') {
            // Extract last 4 bytes as challenge (if present)
            if ($len >= 9) {
                $challenge = substr($result, -4);
            } else {
                $this->errstr = 'Invalid challenge response';

                return false;
            }

            // Build original A2S_INFO command and append the challenge
            $infoCommand = "\xFF\xFF\xFF\xFF\x54\x53\x6F\x75\x72\x63\x65\x20\x45\x6E\x67\x69\x6E\x65\x20\x51\x75\x65\x72\x79\x00" . $challenge;

            // Retry once to obtain the full info response
            $retryResult = $this->sendCommand($address, $port, $infoCommand);

            if ($retryResult === '' || $retryResult === '0' || $retryResult === false) {
                $this->errstr = 'Failed to retrieve info after challenge';

                return false;
            }

            $result = $retryResult;
            $len    = strlen($result);
            // reset index to parse the new full response
            $i                   = 4;
            $firstTypeChar       = $result[$i++] ?? "\0";
            $this->rules['Type'] = ($firstTypeChar === 'I' || $firstTypeChar === 'A') ? 'Source' : 'HL1';
        }

        if ($this->rules['Type'] === 'Source') {
            if ($i >= $len) {
                $this->errstr = 'Invalid response when reading network version';

                return false;
            }

            $this->rules['NetworkVersion'] = ord(substr($result, $i++, 1));

            while ($i < $len && (($result[$i] ?? "\0") !== chr(0))) {
                $this->servertitle .= $result[$i++] ?? '';
            }

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading servertitle';

                return false;
            }

            $i++;

            while ($i < $len && (($result[$i] ?? "\0") !== chr(0))) {
                $this->mapname .= $result[$i++] ?? '';
            }

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading mapname';

                return false;
            }

            $i++;

            while ($i < $len && (($result[$i] ?? "\0") !== chr(0))) {
                $this->rules['gamedir'] = $this->rules['gamedir'] . $result[$i++];
            }

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading gamedir';

                return false;
            }

            $i++;

            while ($i < $len && (($result[$i] ?? "\0") !== chr(0))) {
                $this->gamename .= $result[$i++] ?? '';
            }

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading gamename';

                return false;
            }

            $i++;

            if ($i + 1 >= $len) {
                $this->errstr = 'Invalid response while reading appid';

                return false;
            }

            $tmp                  = @unpack('n', substr($result, $i, 2));
            $this->rules['appid'] = is_array($tmp) && isset($tmp[1]) ? $tmp[1] : 0;
            $i                    = $i + 2;

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading player counts';

                return false;
            }

            $this->numplayers          = ord(substr($result, $i++, 1));
            $this->maxplayers          = ord(substr($result, $i++, 1));
            $this->rules['botplayers'] = ord(substr($result, $i++, 1));

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading server flags';

                return false;
            }

            $this->rules['dedicated'] = ($result[$i++] === 'd' ? 'Yes' : 'No');
            $this->rules['server_os'] = ($result[$i++] === 'l' ? 'Linux' : 'Windows');

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading password flag';

                return false;
            }

            $this->password        = ord(substr($result, $i++, 1));
            $this->rules['secure'] = ($result[$i++] === '1' ? 'Yes' : 'No');

            while ($i < $len && (($result[$i] ?? "\0") !== chr(0))) {
                $this->gameversion .= $result[$i++] ?? '';
            }

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading gameversion';

                return false;
            }

            $i++;

            // Extra Data Flag (EDF) handling
            if ($i < $len) {
                $edf = ord(substr($result, $i, 1));
                $i++;

                if (($edf & 0x80) !== 0) {
                    $this->rules['port'] = $this->readInt16Signed($result, $i);
                }

                if (($edf & 0x10) !== 0) {
                    $this->rules['steam_id'] = $this->readInt64($result, $i);
                }

                if (($edf & 0x40) !== 0) {
                    $this->rules['sourcetv_port'] = $this->readInt16Signed($result, $i);
                    $this->rules['sourcetv_name'] = $this->readString($result, $i);
                }

                if (($edf & 0x20) !== 0) {
                    $this->rules['keywords'] = $this->readString($result, $i);
                }

                if (($edf & 0x01) !== 0) {
                    $this->rules['game_id'] = $this->readInt64($result, $i);
                }
            }
        } else { // For HL 1
            while ($i < $len && $result[$i] !== chr(0)) {
                $this->rules['IP'] = ($this->rules['IP'] ?? '') . $result[$i++];
            }

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading IP';

                return false;
            }
            $i++;

            while ($i < $len && $result[$i] !== chr(0)) {
                $this->servertitle .= $result[$i++];
            }

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading servertitle (HL1)';

                return false;
            }
            $i++;

            while ($i < $len && $result[$i] !== chr(0)) {
                $this->mapname .= $result[$i++];
            }

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading mapname (HL1)';

                return false;
            }
            $i++;

            while ($i < $len && $result[$i] !== chr(0)) {
                $this->rules['gamedir'] = ($this->rules['gamedir'] ?? '') . $result[$i++];
            }

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading gamedir (HL1)';

                return false;
            }
            $i++;

            while ($i < $len && $result[$i] !== chr(0)) {
                $this->rules['gamename'] = ($this->rules['gamename'] ?? '') . $result[$i++];
            }

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading gamename (HL1)';

                return false;
            }
            // while ($result[$i]!=chr(0)) $this->gamename.=$result[$i++];
            $i++;

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading player counts (HL1)';

                return false;
            }

            $this->numplayers = ord(substr($result, $i++, 1));

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading maxplayers (HL1)';

                return false;
            }
            $this->maxplayers = ord(substr($result, $i++, 1));

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading gameversion (HL1)';

                return false;
            }
            $this->gameversion = (string) ord(substr($result, $i++, 1));

            if ($this->gameversion === '47') {
                $this->gameversion .= ' (1.6)';
            }

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading server flags (HL1)';

                return false;
            }
            $this->rules['dedicated'] = ($result[$i++] === 'd' ? 'Yes' : 'No');

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading server OS (HL1)';

                return false;
            }
            $this->rules['server_os'] = ($result[$i++] === 'l' ? 'Linux' : 'Windows');

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading password (HL1)';

                return false;
            }
            $this->password = ord(substr($result, $i++, 1));

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading secure flag (HL1)';

                return false;
            }
            $this->rules['secure'] = ($result[$i++] === '1' ? 'Yes' : 'No');

            while ($i < $len && $result[$i] !== chr(0)) {
                $this->rules['mod_url'] = ($this->rules['mod_url'] ?? '') . $result[$i++];
            }

            if ($i >= $len) {
                $this->errstr = 'Invalid response while reading mod_url (HL1)';

                return false;
            }
            $i++;
        }

        // do rules
        // challange
        $command = "\xFF\xFF\xFF\xFF\x57";

        if (($result = $this->sendCommand($address, $port, $command)) !== false) {
            $challenge = substr($result, -4);
            // query
            $command = "\xFF\xFF\xFF\xFF\x56";

            if (($result = $this->sendCommand($address, $port, $command . $challenge)) !== false) {
                // Process rules...
                if ($this->rules['Type'] === 'HL1') {
                    // rules can be in multiple packets in 1.6, we have to sort it out
                    // First packet has a 16 byte header, subsequent packet has an 8 byte header.
                    $str = "/\xFE\xFF\xFF\xFF/"; // packet signature (both first and second start with this)

                    $block = preg_split($str, $result, -1, PREG_SPLIT_NO_EMPTY);

                    $str = "/\xFF\xFF\xFF\xFF/"; // first packet signature (only first packet matches this)

                    if (isset($block[0]) && ($block[0] !== '' && $block[0] !== '0') && (isset($block[1]) && ($block[1] !== '' && $block[1] !== '0'))) {
                        if (preg_match($str, $block[0]) !== false) {
                            $result = substr($block[0], 12, strlen($block[0])) . substr($block[1], 5, strlen($block[1]));
                        } elseif (preg_match($str, $block[1]) !== false) {
                            $result = substr($block[1], 12, strlen($block[1])) . substr($block[0], 5, strlen($block[1])) . substr($block[0], 5, strlen($block[0]));
                        }
                    } elseif (isset($block[0]) && ($block[0] !== '' && $block[0] !== '0')) {
                        $result = substr($block[0], 5, strlen($block[0]));
                    }
                    $j = 0; // beginning value off for
                } else {
                    $j = 1; // beginning value off for
                }

                $exploded_data  = explode(chr(0), $result);
                $this->password = -1;
                $z              = count($exploded_data);

                $idx = $j;

                while ($idx < $z - 1) {
                    $key   = $exploded_data[$idx++] ?? '';
                    $value = $exploded_data[$idx++] ?? '';

                    switch ($key) {
                        case 'sv_password':
                            $this->password = (int) $value;

                            break;

                        case 'deathmatch':
                            if ($value === '1') {
                                $this->gametype = 'Deathmatch';
                            }

                            break;

                        case 'coop':
                            if ($value === '1') {
                                $this->gametype = 'Cooperative';
                            }

                            break;

                        default:
                            $this->rules[$key] = $value;
                    }
                }
            }
        }

        if ($getPlayers) {
            // challange
            $command = "\xFF\xFF\xFF\xFF\x57";

            if (($result = $this->sendCommand($address, $port, $command)) !== false) {
                $challenge = substr($result, -4);
                // query
                $command = "\xFF\xFF\xFF\xFF\x55";

                if (($result = $this->sendCommand($address, $port, $command . $challenge)) !== false) {
                    $this->processPlayers($result, $this->playerFormat, 8);

                    $this->playerkeys['name']  = true;
                    $this->playerkeys['score'] = true;
                    $this->playerkeys['time']  = true;
                }
            }
        }

        $this->online = true;

        return true;
    }

    /**
     * processPlayers method.
     *
     * @psalm-param 8 $formatLength
     *
     * @return null|false
     */
    public function processPlayers(string $data, string $format, int $formatLength): ?bool
    {
        $len = strlen($data);

        for ($i = 6; $i < $len; $i = $endPlayerName + $formatLength + 1) {
            // finding end of player name
            $endPlayerName = strpos($data, "\x00", ++$i);

            if ($endPlayerName === false) {
                return false;
            } // abort on bogus data

            // unpacking player's score and time
            $unpacked  = unpack('@' . ($endPlayerName + 1) . $format, $data);
            $curPlayer = $unpacked !== false ? $unpacked : [];
            /** @var array<string, mixed> $curPlayer */

            // format time
            if (array_key_exists('time', $curPlayer) && is_numeric($curPlayer['time']) && is_finite((float) $curPlayer['time']) && $curPlayer['time'] >= 0 && $curPlayer['time'] <= 86400) {
                $timestamp         = mktime(0, 0, (int) $curPlayer['time']);
                $curPlayer['time'] = $timestamp !== false ? date('H:i:s', $timestamp) : '00:00:00';
            } else {
                $curPlayer['time'] = '00:00:00'; // default if invalid
            }
            // extract player name
            $curPlayer['name'] = substr($data, $i, $endPlayerName - $i);
            // add player to the list of players
            $this->players[] = $curPlayer;
        }

        return null;
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
     * @psalm-param int<21, max> $i
     */
    private function readInt16Signed(string $data, int &$i): int
    {
        $unpacked = unpack('s', substr($data, $i, 2));

        if ($unpacked === false) {
            $val = [1 => 0];
        } else {
            $val = $unpacked;
        }
        $i += 2;

        /** @var array{1: int} $val */
        return (int) $val[1];
    }

    /**
     * @psalm-param int<21, max> $i
     */
    private function readInt64(string $data, int &$i): int
    {
        $unpacked = unpack('q', substr($data, $i, 8));

        if ($unpacked === false) {
            $val = [1 => 0];
        } else {
            $val = $unpacked;
        }
        $i += 8;

        /** @var array{1: int} $val */
        return (int) $val[1];
    }

    /**
     * @psalm-param int<21, max> $i
     */
    private function readString(string $data, int &$i): string
    {
        $str = '';

        while ($i < strlen($data) && $data[$i] !== chr(0)) {
            $str .= $data[$i++];
        }
        $i++; // skip null

        return $str;
    }
}
