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

use function count;
use function explode;
use function htmlentities;
use function is_string;
use function preg_match;
use function preg_replace;
use function preg_split;
use function sprintf;
use function str_repeat;
use function strlen;
use function strtolower;
use function substr;
use Override;

/**
 * Implements the query protocol for Quake 3 Arena servers.
 * Handles server information retrieval, player lists, and game-specific data parsing.
 */
class Quake3Arena extends Quake
{
    /**
     * Protocol name.
     */
    public string $name = 'Quake 3 Arena';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Quake 3 Arena', 'Warsow', 'Call of Duty', 'Call of Duty 2', 'Call of Duty 4'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'Quake3';

    /**
     * Game series.
     *
     * @var array<string>
     */
    public array $game_series_list = ['Quake'];

    /**
     * query_server method.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        if ($this->online) {
            $this->reset();
        }

        $address = (string) $this->address;
        $port    = (int) $this->queryport;

        $command = "\xFF\xFF\xFF\xFF\x02getstatus\x0a\x00";

        if (($result = $this->sendCommand($address, $port, $command)) === '' || ($result = $this->sendCommand($address, $port, $command)) === '0' || ($result = $this->sendCommand($address, $port, $command)) === false) {
            $this->errstr = 'No reply received';

            return false;
        }

        $temp    = explode("\x0a", $result);
        $rawdata = [];

        if (isset($temp[1]) && is_string($temp[1])) {
            $rawdata = explode('\\', substr($temp[1], 1, strlen($temp[1])));
        }

        // get rules and basic infos
        for ($i = 0; $i < count($rawdata); $i++) {
            $key = $rawdata[$i] ?? '';
            $i++;

            switch ($key) {
                case 'g_gametypestring':
                    $this->gametype = $rawdata[$i] ?? '';

                    break;

                case 'gamename':
                    $this->gametype = $rawdata[$i] ?? '';

                    $this->gamename = 'q3a_' . preg_replace('/[ :]/', '_', strtolower($rawdata[$i] ?? ''));

                    break;

                case 'version':
                    // for CoD
                case 'shortversion':
                    $this->gameversion = $rawdata[$i] ?? '';

                    break;

                case 'sv_hostname':
                    $this->servertitle = $rawdata[$i] ?? '';

                    break;

                case 'mapname':
                    $this->mapname = $rawdata[$i] ?? '';

                    break;

                case 'g_needpass':
                    // for CoD
                case 'pswrd':
                    $this->password = isset($rawdata[$i]) ? (int) $rawdata[$i] : 0;

                    break;

                case 'sv_maplist':
                    $tmp           = preg_split('#( )+#', $rawdata[$i] ?? '');
                    $tmp           = $tmp !== false ? $tmp : [];
                    $this->maplist = $tmp;

                    break;

                case 'sv_privateclients':
                    $this->rules['sv_privateClients'] = $rawdata[$i] ?? 0;

                    break;

                default:
                    $this->rules[$rawdata[$i - 1] ?? ''] = $rawdata[$i] ?? '';
            }
        }

        // for MoHAA
        if ($this->gamename === '' && preg_match('/Medal of Honor/Di', $this->gameversion) === 1) {
            $this->gamename = 'mohaa';
        }

        if (count($this->maplist) > 0) {
            $i             = 0;
            $this->nextmap = $this->maplist[$i % count($this->maplist)] ?? '';
        }

        // for MoHAA
        $this->mapname = preg_replace('/.*\//', '', $this->mapname) ?? $this->mapname;

        $this->hostport   = $this->queryport ?? 0;
        $this->maxplayers = (int) ($this->rules['sv_maxclients'] ?? 0) - (int) ($this->rules['sv_privateClients'] ?? 0);

        // get playerdata
        $temp             = substr($result, strlen($temp[0] ?? '') + strlen($temp[1] ?? '') + 1, strlen($result));
        $allplayers       = explode("\n", $temp);
        $this->numplayers = count($allplayers) - 2;

        // get players
        if (count($allplayers) - 2 > 0 && $getPlayers) {
            $players  = [];
            $pingOnly = false;
            $teamInfo = false;

            for ($i = 1; $i < count($allplayers) - 1; $i++) {
                $line = $allplayers[$i] ?? '';

                // match with team info
                if (preg_match("/(\d+)[^0-9](\d+)[^0-9]\"(.*)\"/", $line, $curplayer) !== false) {
                    // ignore spectators (team > 2)
                    /** @phpstan-ignore offsetAccess.notFound */
                    if ((int) $curplayer[3] > 2) {
                        // ignore spectators
                    }

                    /** @phpstan-ignore offsetAccess.notFound */
                    $players[$i - 1]['name'] = $curplayer[3];

                    /** @phpstan-ignore offsetAccess.notFound */
                    $players[$i - 1]['score'] = (int) $curplayer[1];

                    /** @phpstan-ignore offsetAccess.notFound */
                    $players[$i - 1]['ping'] = (int) $curplayer[2];

                    /** @phpstan-ignore offsetAccess.notFound */
                    $players[$i - 1]['team'] = $curplayer[3];
                    $teamInfo                = true;
                    $pingOnly                = false;
                }

                /** @phpstan-ignore notIdentical.alwaysFalse */ elseif (preg_match("/(\d+)[^0-9](\d+)[^0-9]\"(.*)\"/", $line, $curplayer) !== false) {
                    /** @phpstan-ignore offsetAccess.notFound */
                    $players[$i - 1]['name'] = $curplayer[3];

                    /** @phpstan-ignore offsetAccess.notFound */
                    $players[$i - 1]['score'] = (int) $curplayer[1];

                    /** @phpstan-ignore offsetAccess.notFound */
                    $players[$i - 1]['ping'] = (int) $curplayer[2];
                    $pingOnly                = false;
                    $teamInfo                = false;
                } else {
                    if (preg_match("/(\d+).\"(.*)\"/", $line, $curplayer) !== false) {
                        /** @phpstan-ignore offsetAccess.notFound */
                        $players[$i - 1]['name'] = $curplayer[2];

                        /** @phpstan-ignore offsetAccess.notFound */
                        $players[$i - 1]['ping'] = (int) $curplayer[1];
                        $pingOnly                = true; // for MoHAA
                    } else {
                        $this->errstr = 'Could not extract player infos!';

                        return false;
                    }
                }
            }
            $this->playerkeys['name'] = true;

            if (!$pingOnly) {
                $this->playerkeys['score'] = true;

                if ($teamInfo) {
                    $this->playerkeys['team'] = true;
                }
            }
            $this->playerkeys['ping'] = true;
            $this->players            = $players;
        }

        $this->online = true;

        return true;
    }

    /**
     *  htmlizes the given raw string.
     *
     * @param string $var a raw string from the gameserver that might contain special chars
     */
    public function htmlize(string $var): string
    {
        $len     = strlen($var);
        $numTags = 0;
        $result  = '';
        $var .= '  '; // padding
        $colortag = '<span class="csQuery-%s-%s">';

        $csstype = match ($this->gamename) {
            'q3a_Call_of_Duty', 'q3a_sof2' => 'q3a_exdended',
            default => 'q3a',
        };

        for ($i = 0; $i < $len; $i++) {
            // checking for a color code
            if ($var[$i] === '^') {
                $numTags++; // count tags

                match ($var[++$i]) {
                    '<'     => $result .= sprintf($colortag, $csstype, 'less'),
                    '>'     => $result .= sprintf($colortag, $csstype, 'greater'),
                    '&'     => $result .= sprintf($colortag, $csstype, 'and'),
                    '\''    => $result .= sprintf($colortag, $csstype, 'tick'),
                    '='     => $result .= sprintf($colortag, $csstype, 'equal'),
                    '?'     => $result .= sprintf($colortag, $csstype, 'questionmark'),
                    '.'     => $result .= sprintf($colortag, $csstype, 'point'),
                    ','     => $result .= sprintf($colortag, $csstype, 'comma'),
                    '!'     => $result .= sprintf($colortag, $csstype, 'exc'),
                    '*'     => $result .= sprintf($colortag, $csstype, 'star'),
                    '$'     => $result .= sprintf($colortag, $csstype, 'dollar'),
                    '#'     => $result .= sprintf($colortag, $csstype, 'pound'),
                    '('     => $result .= sprintf($colortag, $csstype, 'lparen'),
                    ')'     => $result .= sprintf($colortag, $csstype, 'rparen'),
                    '@'     => $result .= sprintf($colortag, $csstype, 'at'),
                    '%'     => $result .= sprintf($colortag, $csstype, 'percent'),
                    '+'     => $result .= sprintf($colortag, $csstype, 'plus'),
                    '|'     => $result .= sprintf($colortag, $csstype, 'bar'),
                    '{'     => $result .= sprintf($colortag, $csstype, 'lbracket'),
                    '}'     => $result .= sprintf($colortag, $csstype, 'rbracket'),
                    '"'     => $result .= sprintf($colortag, $csstype, 'quote'),
                    ':'     => $result .= sprintf($colortag, $csstype, 'colon'),
                    '['     => $result .= sprintf($colortag, $csstype, 'lsqr'),
                    ']'     => $result .= sprintf($colortag, $csstype, 'rsqr'),
                    '\\'    => $result .= sprintf($colortag, $csstype, 'lslash'),
                    '/'     => $result .= sprintf($colortag, $csstype, 'rslash'),
                    ';'     => $result .= sprintf($colortag, $csstype, 'semic'),
                    '^'     => $result .= '^<span class="csQuery-' . $csstype . '-' . $var[++$i] . '">',
                    default => $result .= sprintf($colortag, $csstype, $var[$i]),
                };
            } else {
                // normal char
                $result .= htmlentities($var[$i]);
            }
        }

        // appending numTags spans
        return $result . str_repeat('</span>', $numTags);
    }
}
