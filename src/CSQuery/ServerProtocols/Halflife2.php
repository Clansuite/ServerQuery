<?php

declare(strict_types=1);

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

use function count;
use function explode;
use function is_numeric;
use function is_scalar;
use function ord;
use function preg_replace;
use function strtolower;
use function substr;
use Override;

/**
 * This class implements the protocol used by Half-Life 2.
 */
class Halflife2 extends Halflife
{
    /**
     * Protocol name.
     */
    public string $name = 'Half-Life 2';

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Halflife2';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Half-Life'];

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Half-Life 2'];

    /**
     * query_server method.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        if ($this->online) {
            $this->reset();
        }

        $address = is_scalar($this->address) ? $this->address : '';
        $port    = is_numeric($this->queryport) ? $this->queryport : 0;

        $command = "\xFF\xFF\xFF\xFFT";

        if (($result = $this->sendCommand($address, $port, $command)) === '' || ($result = $this->sendCommand($address, $port, $command)) === '0' || ($result = $this->sendCommand($address, $port, $command)) === false) {
            return false;
        }

        $i = 5; // start after header

        $this->gameversion = (string) ord($result[5]);
        $this->hostport    = $this->queryport ?? 0;

        $basic = explode("\x00", substr($result, 6));

        // XXX: Replace old code
        $this->rules['gamedir'] = '';

        if (isset($basic[0])) {
            $this->servertitle = $basic[0];
        }

        if (isset($basic[1])) {
            $this->mapname = $basic[1];
        }

        if (isset($basic[2])) {
            $this->rules['gamedir'] = $basic[2];
        }

        $this->gamename         = isset($basic[3]) ? preg_replace('/[ :]/', '_', strtolower($basic[3])) ?? '' : '';
        $this->rules['steamid'] = ord($result[$i]) | (ord($result[$i + 1]) << 8);
        $i += 2;
        $this->numplayers          = ord(substr($result, $i++, 1));
        $this->maxplayers          = ord(substr($result, $i++, 1));
        $this->rules['botplayers'] = ord(substr($result, $i++, 1));
        $this->rules['dedicated']  = ($result[$i++] === 'd' ? 'Yes' : 'No');
        $this->rules['server_os']  = ($result[$i++] === 'l' ? 'Linux' : 'Windows');
        $this->password            = ord(substr($result, $i++, 1));
        $this->rules['secure']     = ($result[$i++] === '1' ? 'Yes' : 'No');

        // Already normalized above

        // do rules
        $command = "\xFF\xFF\xFF\xFFV";

        if (($result = $this->sendCommand($address, $port, $command)) === '' || ($result = $this->sendCommand($address, $port, $command)) === '0' || ($result = $this->sendCommand($address, $port, $command)) === false) {
            return false;
        }

        $exploded_data = explode("\x00", $result);

        $z = count($exploded_data);

        $i = 1;

        while ($i < $z) {
            $key   = $exploded_data[$i++] ?? '';
            $value = $exploded_data[$i++] ?? '';

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

        if ($getPlayers) {
            // do players
            $command = "\xFF\xFF\xFF\xFFU";

            if (($result = $this->sendCommand($address, $port, $command)) === '' || ($result = $this->sendCommand($address, $port, $command)) === '0' || ($result = $this->sendCommand($address, $port, $command)) === false) {
                return false;
            }

            $this->processPlayers($result, $this->playerFormat, 8);

            $this->playerkeys['name']  = true;
            $this->playerkeys['score'] = true;
            $this->playerkeys['time']  = true;
        }

        $this->online = true;

        return true;
    }
}
