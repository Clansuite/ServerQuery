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

namespace Clansuite\Capture\Extractor;

use function get_object_vars;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use Clansuite\Capture\ServerInfo;

/**
 * Extracts server information from a query result into a structured ServerInfo object.
 */
final class ServerInfoExtractor
{
    /**
     * extract method.
     */
    public function extract(object $server): ServerInfo
    {
        /** @var array<string, mixed> $data */
        $data = get_object_vars($server);

        $rules       = $data['rules'] ?? null;
        $players     = $data['players'] ?? null;
        $address     = $data['address'] ?? null;
        $queryport   = $data['queryport'] ?? null;
        $online      = $data['online'] ?? null;
        $gamename    = $data['gamename'] ?? null;
        $gameversion = $data['gameversion'] ?? null;
        $servertitle = $data['servertitle'] ?? null;
        $mapname     = $data['mapname'] ?? null;
        $gametype    = $data['gametype'] ?? null;
        $numplayers  = $data['numplayers'] ?? null;
        $maxplayers  = $data['maxplayers'] ?? null;
        $errstr      = $data['errstr'] ?? null;

        /** @var array<string, mixed> $rules */
        $rules = is_array($rules) ? $rules : [];

        /** @var array<array<string, mixed>> $players */
        $players = is_array($players) ? $players : [];

        return new ServerInfo(
            address: is_string($address) ? $address : null,
            queryport: is_int($queryport) ? $queryport : null,
            online: is_bool($online) ? $online : false,
            gamename: is_string($gamename) ? $gamename : null,
            gameversion: is_string($gameversion) ? $gameversion : null,
            servertitle: is_string($servertitle) ? $servertitle : null,
            mapname: is_string($mapname) ? $mapname : null,
            gametype: is_string($gametype) ? $gametype : null,
            numplayers: is_int($numplayers) ? $numplayers : 0,
            maxplayers: is_int($maxplayers) ? $maxplayers : 0,
            rules: $rules,
            players: $players,
            errstr: is_string($errstr) ? $errstr : null,
        );
    }
}
