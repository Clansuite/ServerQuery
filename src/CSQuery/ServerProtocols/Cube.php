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
use function count;
use function is_int;
use function is_string;
use function ord;
use function preg_replace;
use function strlen;
use function substr;
use Clansuite\ServerQuery\CSQuery;
use Clansuite\ServerQuery\Util\UdpClient;
use Exception;
use Override;

/**
 * Cube Engine protocol implementation.
 *
 * Used for Cube 1, Assault Cube, Cube 2: Sauerbraten, Blood Frontier.
 */
class Cube extends CSQuery
{
    /**
     * Extended ping commands.
     */
    private const EXTPING_NAMELIST = "\x01\x01";

    private const EXT_PLAYERSTATS = "\x00\x01";

    /**
     * Protocol name.
     */
    public string $name = 'Cube Engine';

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Cube';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = [
        'Cube 1',
        'Assault Cube',
        'Cube 2: Sauerbraten',
        'Blood Frontier',
    ];

    /**
     * Game mode names.
     *
     * @var string[]
     */
    private array $modeNames = [
        'DEMO', 'TDM', 'coop', 'DM', 'SURV', 'TSURV', 'CTF', 'PF', 'BTDM', 'BDM', 'LSS',
        'OSOK', 'TOSOK', 'BOSOK', 'HTF', 'TKTF', 'KTF', 'TPF', 'TLSS', 'BPF', 'BLSS', 'BTSURV', 'BTOSOK',
    ];

    /**
     * State names.
     *
     * @var string[]
     */
    private array $stateNames = [
        'alive', 'dead', 'spawning', 'lagged', 'editing', 'spectating',
    ];

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

        // Cube Engine queries use port + 1
        $queryPort = ($this->queryport ?? 0) + 1;

        // Send EXTPING_NAMELIST to get basic server info and player names
        $result1 = $this->sendCommand($this->address ?? '', $queryPort, self::EXTPING_NAMELIST);

        if ($result1 === '' || $result1 === '0' || $result1 === false) {
            $this->errstr = 'No reply received for server info';

            return false;
        }

        // Parse server info response
        $buffer = new CubeReadBuffer($result1);

        // Skip extping_code (2 bytes)
        $buffer->getUChar();
        $buffer->getUChar();

        // Skip proto_version (3 bytes)
        $buffer->getUChar();
        $buffer->getUChar();
        $buffer->getUChar();

        $gamemode           = $buffer->getInt();
        $nbConnectedClients = $buffer->getInt();
        $buffer->getInt();
        $serverMap         = $buffer->getString();
        $serverDescription = $this->filterText($buffer->getString());
        $maxClients        = $buffer->getInt();

        // Mastermode (2 bytes)
        $mastermode1 = $buffer->getUChar();
        $buffer->getUChar();

        // Convert mastermode
        $mastermode = 'open'; // default

        if ($mastermode1 === 64 || $mastermode1 === 65) {
            $mastermode = 'private';
        } elseif ($mastermode1 === -128) {
            $mastermode = 'match';
        }

        // Read player names
        $playerNames = [];

        while (!$buffer->isEmpty()) {
            $playerName = $buffer->getString();

            if ($playerName === '') {
                break;
            }
            $playerNames[] = $playerName;
        }

        // Set basic server info
        $this->servertitle = $serverDescription;
        $this->mapname     = $serverMap;
        $this->maxplayers  = $maxClients;
        $this->numplayers  = $nbConnectedClients;
        $this->password    = 0; // Not available in this response

        // Set game type
        if (isset($this->modeNames[$gamemode])) {
            $this->gametype = $this->modeNames[$gamemode];
        }

        // Get detailed player stats if requested
        if ($getPlayers && $nbConnectedClients > 0) {
            $this->parsePlayerStats($queryPort);
        }

        $this->online = true;

        return true;
    }

    private function parsePlayerStats(int $queryPort): void
    {
        // Create UDP client for multi-packet queries
        /** @var UdpClient $udpClient */
        $udpClient = new UdpClient;
        $udpClient->setTimeout(3); // 3 second timeout

        // Send EXT_PLAYERSTATS + \xff to get all player stats
        $command = self::EXT_PLAYERSTATS . "\xff";
        $packets = $udpClient->queryMultiPacket($this->address ?? '', $queryPort, $command, 0, 0.5); // 0.5s between packets

        if ($packets === []) {
            return;
        }

        if (!isset($packets[0])) {
            return;
        }

        // First packet should contain client numbers
        $buffer = new CubeReadBuffer($packets[0]);

        // Skip extping_code (2 bytes)
        $buffer->getUChar();
        $buffer->getUChar();

        // Skip proto_version (3 bytes)
        $buffer->getUChar();
        $buffer->getUChar();
        $buffer->getUChar();

        // EXT_PLAYERSTATS_RESP_IDS should be -10
        $buffer->getUChar();
        $respType2 = $buffer->getUChar();

        if ($respType2 !== 246) { // -10 as signed char
            return; // Invalid response
        }

        // Read client numbers
        $clientNumbers = [];

        while (!$buffer->isEmpty()) {
            try {
                $clientNum       = $buffer->getInt();
                $clientNumbers[] = $clientNum;
            } catch (Exception) {
                break;
            }
        }

        // Remaining packets should be player data (skip first packet)
        $players     = [];
        $packetIndex = 1;

        foreach ($clientNumbers as $clientNum) {
            if ($packetIndex >= count($packets) || !array_key_exists($packetIndex, $packets)) {
                break; // No more packets
            }

            $playerResult = $packets[$packetIndex];
            $packetIndex++;
            $playerBuffer = new CubeReadBuffer($playerResult);

            try {
                // Skip headers
                $playerBuffer->getUChar(); // extping_code
                $playerBuffer->getUChar();
                $playerBuffer->getUChar(); // proto_version
                $playerBuffer->getUChar();
                $playerBuffer->getUChar();
                $playerBuffer->getUChar(); // EXT_PLAYERSTATS_RESP_STATS
                $playerBuffer->getUChar();

                $pClientNum = $playerBuffer->getInt();
                $ping       = $playerBuffer->getInt();
                $name       = $playerBuffer->getString();
                $team       = $playerBuffer->getString();
                $frags      = $playerBuffer->getInt();
                $flags      = $playerBuffer->getInt();
                $deaths     = $playerBuffer->getInt();
                $teamkills  = $playerBuffer->getInt();
                $accuracy   = $playerBuffer->getInt();
                $health     = $playerBuffer->getInt();
                $armour     = $playerBuffer->getInt();
                $gun        = $playerBuffer->getInt();
                $role       = $playerBuffer->getInt();
                $state      = $playerBuffer->getInt();

                // IP (3 bytes)
                $ip1 = $playerBuffer->getUChar();
                $ip2 = $playerBuffer->getUChar();
                $ip3 = $playerBuffer->getUChar();
                $ip  = "{$ip1}.{$ip2}.{$ip3}.0";

                // Additional stats if available
                $damage     = -1;
                $shotdamage = -1;

                if (!$playerBuffer->isEmpty()) {
                    $damage = $playerBuffer->getInt();
                }

                if (!$playerBuffer->isEmpty()) {
                    $shotdamage = $playerBuffer->getInt();
                }

                if ($name !== '' && $name !== '0') {
                    $players[] = [
                        'name'       => $name,
                        'score'      => $frags,
                        'ping'       => $ping,
                        'team'       => $team,
                        'deaths'     => $deaths,
                        'health'     => $health,
                        'armour'     => $armour,
                        'accuracy'   => $accuracy,
                        'ip'         => $ip,
                        'state'      => $this->stateNames[$state] ?? 'unknown',
                        'role'       => $role === 1 ? 'admin' : 'player',
                        'damage'     => $damage,
                        'shotdamage' => $shotdamage,
                    ];
                }
            } catch (Exception) {
                // Skip malformed packets
                continue;
            }
        }

        $this->players    = $players;
        $this->playerkeys = [
            'name'       => true,
            'score'      => true,
            'ping'       => true,
            'team'       => true,
            'deaths'     => true,
            'health'     => true,
            'armour'     => true,
            'accuracy'   => true,
            'ip'         => true,
            'state'      => true,
            'role'       => true,
            'damage'     => true,
            'shotdamage' => true,
        ];
    }

    private function filterText(string $s): string
    {
        return preg_replace("/\f./", '', $s) ?? '';
    }
}

/**
 * Cube Engine Read Buffer for parsing variable-length encoded data.
 */
class CubeReadBuffer
{
    private int $position;

    /**
     * Constructor.
     */
    public function __construct(private string $data)
    {
        $this->position = 0;
    }

    /**
     * isEmpty method.
     */
    public function isEmpty(): bool
    {
        return $this->position >= strlen($this->data);
    }

    /**
     * hasMore method.
     */
    public function hasMore(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * getUChar method.
     */
    public function getUChar(): int
    {
        if (!$this->hasMore()) {
            throw new Exception('Message is too short');
        }

        $char  = $this->data[$this->position];
        $uchar = ord($char);
        $this->position++;

        return $uchar;
    }

    /**
     * getInt method.
     */
    public function getInt(): int
    {
        $b = $this->getUChar();

        if ($b === 0x80) {
            // 16-bit value
            $low   = $this->getUChar();
            $high  = $this->getUChar();
            $value = $low | ($high << 8);

            return $value < 0x8000 ? $value : $value - 0x10000;
        }

        if ($b === 0x81) {
            // 32-bit value
            $b1    = $this->getUChar();
            $b2    = $this->getUChar();
            $b3    = $this->getUChar();
            $b4    = $this->getUChar();
            $value = $b1 | ($b2 << 8) | ($b3 << 16) | ($b4 << 24);

            return $value < 0x80000000 ? $value : $value - 0x100000000;
        }

        // 8-bit value
        return $b < 0x80 ? $b : $b - 0x100;
    }

    /**
     * getString method.
     */
    public function getString(): string
    {
        $startPosition = $this->position;

        while ($this->hasMore()) {
            if ($this->getUChar() === 0) {
                break;
            }
        }

        return substr($this->data, $startPosition, $this->position - $startPosition - 1);
    }
}
