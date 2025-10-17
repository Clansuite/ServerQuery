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
use function ord;
use function strlen;
use function strpos;
use function substr;
use function unpack;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Exception;
use Override;

/**
 * Tribes 2 protocol implementation.
 *
 * Tribes 2 uses the Torque Game Engine protocol.
 */
class Tribes2 extends Torque implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Tribes 2';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Tribes 2'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Tribes2';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Tribes'];

    /**
     * Returns a native join URI for Tribes 2 or false if not available.
     */
    #[Override]
    public function getNativeJoinURI(): string
    {
        // Tribes 2 uses tribes2:// protocol for joining servers
        return 'tribes2://' . ($this->address ?? '') . ':' . ($this->hostport ?? 0);
    }

    /**
     * Query the Tribes 2 server.
     *
     * Tribes 2 uses a different packet format than the general Torque protocol.
     * Based on LGSL protocol 25 implementation.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        if ($this->online) {
            $this->reset();
        }

        $address = $this->address ?? '';
        $port    = $this->queryport ?? 0;

        // Tribes 2 uses a simple packet format: \x12\x02\x21\x21\x21\x21
        $packet = "\x12\x02\x21\x21\x21\x21";

        if (($response = $this->sendCommand($address, $port, $packet)) === '' || ($response = $this->sendCommand($address, $port, $packet)) === '0' || ($response = $this->sendCommand($address, $port, $packet)) === false) {
            $this->errstr = 'No response from server';

            return false;
        }

        // Remove the 6-byte header from response
        $buffer = substr($response, 6);

        if ($buffer === '' || $buffer === '0') {
            $this->errstr = 'Invalid response from server';

            return false;
        }

        // Parse the response using LGSL-style parsing
        return $this->parseTribes2Response($buffer);
    }

    /**
     * Parse Tribes 2 server response.
     *
     * Based on LGSL protocol 25 parsing.
     */
    private function parseTribes2Response(string $buffer): bool
    {
        try {
            // Game name
            $this->gamename = $this->cutPascalString($buffer);

            // Game mode
            $gamemode       = $this->cutPascalString($buffer);
            $this->gametype = $gamemode;

            // Map name
            $this->mapname = $this->cutPascalString($buffer);

            // Bit flags
            $bitFlags = ord($this->cutByte($buffer, 1));

            // Player counts
            $this->numplayers = ord($this->cutByte($buffer, 1));
            $this->maxplayers = ord($this->cutByte($buffer, 1));

            // Bots
            $bots = ord($this->cutByte($buffer, 1));

            // CPU speed
            $cpuUnpacked = unpack('S', $this->cutByte($buffer, 2));
            $cpu         = is_array($cpuUnpacked) && isset($cpuUnpacked[1]) ? $cpuUnpacked[1] : 0;

            // MOTD
            $motd = $this->cutPascalString($buffer);

            // Unknown field
            $unknownUnpacked = unpack('S', $this->cutByte($buffer, 2));
            $unknown         = is_array($unknownUnpacked) && isset($unknownUnpacked[1]) ? $unknownUnpacked[1] : 0;

            // Parse bit flags
            $this->rules['dedicated']  = (($bitFlags & 1) !== 0) ? '1' : '0';
            $this->password            = (($bitFlags & 2) !== 0) ? 1 : 0;
            $this->rules['os']         = (($bitFlags & 4) !== 0) ? 'L' : 'W';
            $this->rules['tournament'] = (($bitFlags & 8) !== 0) ? '1' : '0';
            $this->rules['no_alias']   = (($bitFlags & 16) !== 0) ? '1' : '0';

            // Additional rules
            $this->rules['bots'] = $bots;
            $this->rules['cpu']  = $cpu;
            $this->rules['motd'] = $motd;

            // Skip team data for now (marked by \x0A)
            $teamData = $this->cutString($buffer, "\x0A");
            // TODO: Parse team data if needed

            // Player data
            $playerCount = (int) $this->cutString($buffer, "\x0A");

            for ($i = 0; $i < $playerCount; $i++) {
                // Skip some unknown bytes
                $this->cutByte($buffer, 1); // ? 16
                $this->cutByte($buffer, 1); // ? 8 or 14 = BOT / 12 = ALIAS / 11 = NORMAL

                if (ord($buffer[0]) < 32) {
                    $this->cutByte($buffer, 1); // ? 8 PREFIXES SOME NAMES
                }

                $playerName = $this->cutString($buffer, "\x11");
                $this->cutString($buffer, "\x09"); // ALWAYS BLANK
                $team  = $this->cutString($buffer, "\x09");
                $score = $this->cutString($buffer, "\x0A");

                $this->players[$i] = [
                    'name'  => $playerName,
                    'team'  => $team,
                    'score' => (int) $score,
                ];
            }

            $this->online = true;

            return true;
        } catch (Exception $e) {
            $this->errstr = 'Failed to parse server response: ' . $e->getMessage();

            return false;
        }
    }

    /**
     * Cut a pascal string (length prefixed).
     */
    private function cutPascalString(string &$buffer): string
    {
        $length = ord($this->cutByte($buffer, 1));

        return $this->cutByte($buffer, $length);
    }

    /**
     * Cut a byte string.
     */
    private function cutByte(string &$buffer, int $length): string
    {
        $result = substr($buffer, 0, $length);
        $buffer = substr($buffer, $length);

        return $result;
    }

    /**
     * Cut string until delimiter.
     */
    private function cutString(string &$buffer, string $delimiter): string
    {
        $pos = strpos($buffer, $delimiter);

        if ($pos === false) {
            $result = $buffer;
            $buffer = '';

            return $result;
        }

        $result = substr($buffer, 0, $pos);
        $buffer = substr($buffer, $pos + strlen($delimiter));

        return $result;
    }
}
