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

use function bzdecompress;
use function count;
use function crc32;
use function explode;
use function function_exists;
use function gmdate;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function ksort;
use function microtime;
use function preg_match;
use function preg_replace;
use function round;
use function str_contains;
use function strlen;
use function strtolower;
use function substr;
use function trim;
use function unpack;
use Clansuite\ServerQuery\CSQuery;
use Clansuite\ServerQuery\Util\PacketReader;
use Override;

/**
 * Queries a Counter Strike 1.6 server.
 *
 * This class works with Counter Strike 1.6 (based on Half-Life engine).
 */
class CounterStrike16 extends CSQuery
{
    /**
     * Protocol name.
     */
    public string $name = 'Counter Strike 1.6';

    /**
     * Protocol identifier.
     */
    public string $protocol = 'CounterStrike16';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Counter-Strike'];

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Counter Strike 1.6'];
    public string $playerFormat  = '/sscore/x2/ftime';
    public float $response       = 0.0;

    /**
     * Constructor.
     */
    public function __construct(mixed $address, mixed $queryport)
    {
        parent::__construct((is_string($address) ? $address : null), (is_int($queryport) ? $queryport : null));
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

        // Use Source engine A2S_INFO query
        $command = "\xFF\xFF\xFF\xFFTSource Engine Query\x00";

        // Try up to three times to obtain a valid response, but preserve the
        // first valid reply to avoid overwriting it with subsequent
        // '(no response)' fixtures from the mock UDP client.
        $attempts = 0;
        $result   = false;

        while ($attempts < 3) {
            $attempts++;
            $tmp = $this->sendCommand((string) $this->address, (int) $this->queryport, $command);

            if ($tmp !== '' && $tmp !== false && $tmp !== '0') {
                $result = $tmp;

                break;
            }

            if ($result === false) {
                $result = $tmp;
            }
        }

        if ($result === '' || $result === '0' || $result === false) {
            $this->errstr = 'No reply received';

            return false;
        }

        $endtime = microtime(true);
        $diff    = round(($endtime - $starttime) * 1000, 0);
        // response time
        $this->response = round($diff, 2);

        // Parse Source engine A2S_INFO response
        if (strlen($result) < 5) {
            $this->errstr = 'Response too short';

            return false;
        }

        // Check header (should be 0xFFFFFFFF)
        $header = $this->unpackFirstValue('N', substr($result, 0, 4));

        if ($header === null) {
            $this->errstr = 'Invalid header unpack';

            return false;
        }

        if ($header !== 4294967295) {
            $this->errstr = 'Invalid header';

            return false;
        }

        // Check response type (should be 'I' for Source engine INFO or 'm' for GoldSource INFO)
        $responseType = substr($result, 4, 1);

        $success = false;

        if ($responseType === 'I') {
            $success = $this->parseSourceInfo($result);
        } elseif ($responseType === 'm') {
            $success = $this->parseGoldSourceInfo($result);
        } else {
            $this->errstr = 'Not an INFO response (got: ' . $responseType . ')';

            return false;
        }

        if (!$success) {
            return false;
        }

        // Get players if requested
        if ($getPlayers && $this->numplayers > 0) {
            $this->getPlayers();
        }

        // Get rules if requested
        if ($getRules) {
            $this->getRules();
        }

        $this->online = true;

        return true;
    }

    /**
     * rcon_query_server method.
     */
    public function rcon_query_server(string $command, string $rcon_pwd): false|string
    {
        $get_challenge = "\xFF\xFF\xFF\xFFchallenge rcon\n";

        $challengeResponse = $this->sendCommand((string) $this->address, (int) $this->queryport, $get_challenge);

        if ($challengeResponse === '' || $challengeResponse === '0' || $challengeResponse === false) {
            $this->debug['Command send ' . $command] = 'No challenge rcon received';

            return false;
        }

        if (preg_match('/challenge rcon (?P<challenge>[0-9]+)/D', $challengeResponse, $matches) !== 1) {
            $this->debug['Command send ' . $command] = 'No valid challenge rcon received';

            return false;
        }

        $challenge      = $matches['challenge'];
        $commandPayload = "\xFF\xFF\xFF\xFFrcon {$challenge} \"{$rcon_pwd}\" {$command}\n";

        $result = $this->sendCommand((string) $this->address, (int) $this->queryport, $commandPayload);

        if ($result === '' || $result === '0' || $result === false) {
            $this->debug['Command send ' . $command] = 'No rcon reply received';

            return false;
        }

        return $result;
    }

    private function getPlayers(): bool
    {
        // Use a Source-style flow: send A2S_PLAYER with -1 and inspect reply
        $playerRequest = "\xFF\xFF\xFF\xFF\x55\xFF\xFF\xFF\xFF";

        // Send initial player request and get raw response using a persistent socket
        $initial = $this->sendCommand((string) $this->address, (int) $this->queryport, $playerRequest);

        if ($initial !== false && strlen($initial) >= 5) {
            // Inspect header
            $header = $this->unpackFirstValue('l', substr($initial, 0, 4));

            if ($header === -1) {
                $respType = substr($initial, 4, 1);

                if ($respType === 'D') {
                    // Full player response returned immediately
                    if ($this->parseBinaryPlayers($initial)) {
                        return true;
                    }
                } elseif ($respType === 'A') {
                    // Challenge returned: extract 4 bytes starting at pos 5 (or 1?)
                    // The challenge is usually the 4 bytes after the response byte
                    if (strlen($initial) >= 9) {
                        $challenge     = substr($initial, 5, 4);
                        $playerCommand = "\xFF\xFF\xFF\xFF\x55" . $challenge;
                        $result        = $this->sendCommand((string) $this->address, (int) $this->queryport, $playerCommand);

                        if ($result !== false && strlen($result) > 5) {
                            if ($this->parseBinaryPlayers($result)) {
                                return true;
                            }
                        }
                    }
                }
            } elseif ($header === -2) {
                // Multi-packet response: collect and assemble
                $assembled = $this->collectMultiPacketResponse($initial);

                if ($assembled !== false && $assembled !== '') {
                    if ($this->parseBinaryPlayers($assembled)) {
                        return true;
                    }
                }
            }
        }

        // Try GoldSource legacy challenge/players flow as a fallback
        if ($this->tryGoldSourcePlayers()) {
            return true;
        }

        // Fallback to text-based status command
        $command = "status\n";
        $result  = $this->sendCommand((string) $this->address, (int) $this->queryport, $command);

        if ($result !== false && $result !== '') {
            return $this->parseTextPlayers($result);
        }

        $this->players = [];

        return true;
    }

    /**
     * Basic multi-packet collector. Uses existing _sendCommand to read subsequent parts
     * and assembles payloads. Supports bzip2 compressed payloads when indicated.
     */
    private function collectMultiPacketResponse(string $firstChunk): false|string
    {
        // parse header similar to PHP-Source-Query's ReadInternal
        $reader = new PacketReader($firstChunk);

        $header = $reader->readInt32();

        if ($header === null || $header !== -2) {
            return false;
        }

        // RequestID (may include compression flag in high bit)
        $requestId = $reader->readInt32();

        if ($requestId === null) {
            return false;
        }

        $isCompressed = ($requestId & 0x80000000) !== 0;

        // Determine Source vs GoldSource split format by peeking next bytes
        $parts = [];

        // Helper to parse a chunk's header and return [count, number, payloadPart, checksum|null]
        $parseChunk = static function (mixed $chunk): ?array
        {
            $r = is_string($chunk) ? new PacketReader($chunk) : null;

            if ($r === null) {
                return null;
            }

            $h = $r->readInt32();

            if ($h === null || $h !== -2) {
                return null;
            }
            $rid = $r->readInt32();

            if ($rid === null) {
                return null;
            }
            $compressed = ($rid & 0x80000000) !== 0;

            // Attempt Source-style parsing
            $remaining = strlen($chunk) - $r->pos();

            if ($remaining >= 2) {
                $packetCount  = $r->readUint8();
                $packetNumber = $r->readUint8();

                if ($packetCount !== null && $packetNumber !== null) {
                    $packetNumber = $packetNumber + 1; // Source packet numbers are 1-based in many libs
                    $checksum     = null;

                    if ($compressed) {
                        // split size (int32) and checksum(uint32)
                        $r->readInt32(); // ignore split size
                        $checksum = $r->readUint32();
                    } else {
                        // split size int16
                        $r->readUint16();
                    }

                    $payloadPart = $r->rest();

                    return [$packetCount, $packetNumber, $payloadPart, $checksum];
                }
            }

            // Fallback to GoldSource-style parsing
            $r = is_string($chunk) ? new PacketReader($chunk) : null;

            if ($r === null) {
                return null;
            }

            $r->readInt32();
            $r->readInt32();
            $b = $r->readUint8();

            if ($b === null) {
                return null;
            }
            $packetCount  = $b & 0xF;
            $packetNumber = $b >> 4;
            $payloadPart  = $r->rest();

            return [$packetCount, $packetNumber, $payloadPart, null];
        };

        // Parse first chunk
        $firstParsed = $parseChunk($firstChunk);

        if ($firstParsed === null) {
            return false;
        }
        [$total, $number, $part, $packetChecksum] = $firstParsed;
        $parts[$number]                           = $part;

        // Collect remaining parts
        $received = count($parts);

        while ($received < $total) {
            $chunk = $this->sendCommand((string) $this->address, (int) $this->queryport, '');

            if ($chunk === false || strlen($chunk) < 8) {
                break;
            }
            $parsed = $parseChunk($chunk);

            if ($parsed === null) {
                break;
            }
            [$t, $n, $p, $chk] = $parsed;
            $parts[$n]         = $p;

            if ($chk !== null) {
                $packetChecksum = $chk;
            }
            $received = count($parts);
        }

        ksort($parts);
        $data = implode('', $parts);

        if ($isCompressed) {
            if (!function_exists('bzdecompress')) {
                return false;
            }
            $decompressed = bzdecompress($data);

            if ($decompressed === false) {
                return false;
            }

            if ($packetChecksum !== null && crc32((string) $decompressed) !== $packetChecksum) {
                return false;
            }

            // As upstream does, strip the first 4 bytes from assembled data
            return substr((string) $decompressed, 4);
        }

        return substr($data, 4);
    }

    private function tryGoldSourcePlayers(): bool
    {
        // Get challenge for GoldSource player query
        $challengeCommand = "\xFF\xFF\xFF\xFF\x57";
        $challengeResult  = $this->sendCommand((string) $this->address, (int) $this->queryport, $challengeCommand);

        if ($challengeResult === false || strlen($challengeResult) < 5) {
            return false;
        }

        // Check header
        $header = $this->unpackFirstValue('N', substr($challengeResult, 0, 4));

        if ($header === null || $header !== 4294967295) {
            return false;
        }

        // Check response type (should be 0x41 'A')
        $responseType = substr($challengeResult, 4, 1);

        if ($responseType !== 'A') {
            return false;
        }

        // Extract challenge number
        if (strlen($challengeResult) < 9) {
            return false;
        }
        $challenge = substr($challengeResult, 5, 4);

        // Query players with challenge
        $playerCommand = "\xFF\xFF\xFF\xFF\x55" . $challenge;
        $result        = $this->sendCommand((string) $this->address, (int) $this->queryport, $playerCommand);

        if ($result === false || strlen($result) < 5) {
            return false;
        }

        return $this->parseGoldSourcePlayers($result);
    }

    private function parseGoldSourcePlayers(string $result): bool
    {
        if (strlen($result) < 5) {
            return false;
        }

        // Check header
        $header = $this->unpackFirstValue('N', substr($result, 0, 4));

        if ($header === null || $header !== 4294967295) {
            return false;
        }

        // Check response type (should be 0x44 'D')
        $responseType = substr($result, 4, 1);

        if ($responseType !== 'D') {
            return false;
        }

        $buf    = substr($result, 5);
        $reader = new PacketReader($buf);

        $numPlayers = $reader->readUint8();

        if ($numPlayers === null) {
            return false;
        }

        $players = [];

        for ($i = 0; $i < $numPlayers; $i++) {
            // Skip player index byte
            if ($reader->readUint8() === null) {
                break;
            }

            $name = $reader->readString();

            if ($name === null) {
                break;
            }

            $score = $reader->readInt32();

            if ($score === null) {
                break;
            }

            $timeValue = $reader->readFloat();

            if ($timeValue === null) {
                break;
            }

            $players[] = [
                'name'  => $name,
                'score' => $score,
                'time'  => gmdate('H:i:s', (int) $timeValue),
            ];
        }

        $this->players = $players;

        return true;
    }

    private function parseBinaryPlayers(string $result): bool
    {
        if (strlen($result) < 5) {
            return false;
        }

        // Check header (should be 0xFFFFFFFF)
        $header = $this->unpackFirstValue('N', substr($result, 0, 4));

        if ($header === null || $header !== 4294967295) {
            return false;
        }

        // Check response type (should be 'D' for PLAYER)
        $responseType = substr($result, 4, 1);

        if ($responseType !== 'D') {
            return false;
        }

        // Parse A2S_PLAYER response
        $buf    = substr($result, 5);
        $reader = new PacketReader($buf);

        $numPlayers = $reader->readUint8();

        if ($numPlayers === null) {
            return false;
        }

        $players = [];

        for ($i = 0; $i < $numPlayers; $i++) {
            if ($reader->readUint8() === null) {
                break;
            }

            $name = $reader->readString();

            if ($name === null) {
                break;
            }

            $scoreRaw = $reader->readUint32();

            if ($scoreRaw === null) {
                break;
            }

            // Convert unsigned to signed 32-bit
            if (($scoreRaw & 0x80000000) !== 0) {
                $scoreRaw -= 0x100000000;
            }

            $timeValue = $reader->readFloat();

            if ($timeValue === null) {
                break;
            }

            // sanitize name: remove non-printable/control characters
            $name = preg_replace('/[[:^print:]]/', '', $name);
            $name = trim((string) $name);

            // Format time: display as H:i:s if >= 3600s, otherwise mm:ss
            $timeInt = (int) round($timeValue);

            if ($timeInt >= 3600) {
                $timeStr = gmdate('H:i:s', $timeInt);
            } else {
                $timeStr = gmdate('i:s', $timeInt);
            }

            $players[] = [
                'name'  => $name,
                'score' => $scoreRaw,
                'time'  => $timeStr,
            ];
        }

        $this->players = $players;

        return true;
    }

    private function parseTextPlayers(string $result): bool
    {
        // Parse text-based status response
        $lines = explode("\n", trim($result));

        $players          = [];
        $inPlayersSection = false;

        /** @var null|array<string, mixed> $currentPlayer */
        $currentPlayer = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if ($line === '0') {
                continue;
            }

            // Look for the start of players section
            if (strtolower($line) === 'players') {
                $inPlayersSection = true;

                continue;
            }

            if (!$inPlayersSection) {
                continue;
            }

            // Check if this is a player number line (like "2.")
            if (preg_match('/^(\d+)\.$/', $line, $matches) === 1) {
                // This is a player number, previous lines should have name and time
                if ($currentPlayer !== null && isset($currentPlayer['name'], $currentPlayer['time'])) {
                    $players[] = [
                        'name'  => $currentPlayer['name'],
                        'score' => $currentPlayer['score'] ?? 0,
                        'time'  => $currentPlayer['time'],
                    ];
                }
                $currentPlayer = ['index' => (int) $matches[1]];

                continue;
            }

            // Check if this looks like a time format (HHH:MM:SS)
            if (preg_match('/^\d{1,3}:\d{2}:\d{2}$/', $line) === 1) {
                if ($currentPlayer !== null) {
                    $currentPlayer['time'] = $line;
                }

                continue;
            }

            // If it doesn't match the above patterns and we have a current player,
            // it might be the player name
            if ($currentPlayer !== null && !isset($currentPlayer['name'])) {
                $currentPlayer['name'] = $line;

                continue;
            }

            // Fallback: try the original format
            if (preg_match('/^#(\d+)\s+"([^"]+)"\s+(\d+)\s+([\d:]+)\s+/', $line, $matches) === 1) {
                $players[] = [
                    'name'  => $matches[2],
                    'score' => (int) $matches[3],
                    'time'  => $matches[4],
                ];
            }
        }

        // Don't forget the last player
        if ($currentPlayer !== null && isset($currentPlayer['name'], $currentPlayer['time'])) {
            $players[] = [
                'name'  => $currentPlayer['name'],
                'score' => $currentPlayer['score'] ?? 0,
                'time'  => $currentPlayer['time'],
            ];
        }

        $this->players = $players;

        return true;
    }

    /**
     * Get rules from the server.
     */
    private function getRules(): bool
    {
        $command = "\xFF\xFF\xFF\xFFrules\x00\x00";

        $result = $this->sendCommand((string) $this->address, (int) $this->queryport, $command);

        if ($result === '' || $result === '0' || $result === false) {
            return false;
        }

        if (strlen($result) < 5) {
            return false;
        }

        // Check header
        $header = $this->unpackFirstValue('N', substr($result, 0, 4));

        if ($header === null || $header !== 4294967295) {
            return false;
        }

        // Check indicator (should be 'E' for rules)
        $indicator = substr($result, 4, 1);

        if ($indicator !== 'E') {
            return false;
        }

        $data = substr($result, 5);
        $pos  = 0;

        if (strlen($data) - $pos < 2) {
            return false;
        }

        $numRulesValue = $this->unpackFirstValue('n', substr($data, $pos, 2));

        if ($numRulesValue === null) {
            return false;
        }
        $pos += 2;

        $rules = [];

        for ($i = 0; $i < $numRulesValue; $i++) {
            if ($pos >= strlen($data)) {
                break;
            }

            $ruleName         = $this->readString($data, $pos);
            $ruleValue        = $this->readString($data, $pos);
            $rules[$ruleName] = $ruleValue;
        }

        $this->rules = $rules;

        return true;
    }

    /**
     * Read a null-terminated string from data.
     */
    private function readString(string $data, int &$pos): string
    {
        $start = $pos;

        while ($pos < strlen($data) && $data[$pos] !== "\x00") {
            $pos++;
        }
        $string = substr($data, $start, $pos - $start);
        $pos++; // Skip null terminator

        return $string;
    }

    /**
     * Safely unpack binary data and return the first value.
     */
    private function unpackFirstValue(string $format, string $data): mixed
    {
        $unpacked = unpack($format, $data);

        if (!is_array($unpacked) || !isset($unpacked[1])) {
            return null;
        }

        return $unpacked[1];
    }

    /**
     * Parse Source engine A2S_INFO response.
     */
    private function parseSourceInfo(mixed $result): bool
    {
        if (!is_string($result) || strlen($result) < 6) {
            return false;
        }

        $reader = new PacketReader(substr($result, 5));

        // Read protocol version (ignored, but must consume the byte)
        if ($reader->readUint8() === null) {
            return false;
        }

        $serverTitle = $reader->readString();
        $mapName     = $reader->readString();
        $gameDir     = $reader->readString();
        $gameName    = $reader->readString();

        if ($serverTitle === null || $mapName === null || $gameDir === null || $gameName === null) {
            return false;
        }

        $appId = $reader->readUint16();

        if ($appId === null) {
            return false;
        }

        if ($reader->remaining() < 3) {
            return false;
        }

        $numPlayers = $reader->readUint8();
        $maxPlayers = $reader->readUint8();
        $password   = $reader->readUint8();

        $gameVersion = $reader->readString();

        if ($gameVersion === null) {
            return false;
        }

        $this->servertitle = $serverTitle;
        $this->mapname     = $mapName;
        $this->gamename    = $gameName;
        $this->steamAppID  = $appId;
        $this->numplayers  = $numPlayers;
        $this->maxplayers  = $maxPlayers;
        $this->password    = $password > 0 ? 1 : 0;
        $this->gameversion = $gameVersion;

        // Set game type
        $this->gametype = 'Counter-Strike 1.6';

        $this->online = true;

        return true;
    }

    /**
     * Parse GoldSource INFO response.
     */
    private function parseGoldSourceInfo(string $result): bool
    {
        if (strlen($result) < 5) {
            return false;
        }

        $header = $this->unpackFirstValue('N', substr($result, 0, 4));

        if ($header === null || $header !== 4294967295) {
            return false;
        }

        $reader = new PacketReader(substr($result, 5));

        // GoldSource INFO layout is:
        //   address, hostname, map, game_dir, game_descr, num_players, max_players, version, ...
        $reader->readString(); // address (ignored)

        $serverTitle = $reader->readString();
        $mapName     = $reader->readString();
        $gameDir     = $reader->readString();
        $gameName    = $reader->readString();

        if ($serverTitle === null || $mapName === null || $gameDir === null || $gameName === null) {
            return false;
        }

        $currentPlayers = $reader->readUint8();
        $maxPlayers     = $reader->readUint8();

        if ($currentPlayers === null || $maxPlayers === null) {
            return false;
        }

        $gameVersion = $reader->readString();

        if ($gameVersion === null) {
            return false;
        }

        $this->servertitle = $serverTitle;
        $this->mapname     = $mapName;
        $this->gamename    = $gameName;
        $this->numplayers  = $currentPlayers;
        $this->maxplayers  = $maxPlayers;
        $this->gameversion = $gameVersion;

        // Set game type based on game directory
        if (str_contains(strtolower($gameDir), 'cstrike')) {
            $this->gametype = 'Counter-Strike 1.6';
        } elseif (str_contains(strtolower($gameDir), 'czero')) {
            $this->gametype = 'Counter-Strike: Condition Zero';
        } else {
            $this->gametype = $this->gamename;
        }

        return true;
    }
}
