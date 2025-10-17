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

        return new ServerInfo(
            address: is_string($data['address'] ?? null) ? $data['address'] : null,
            queryport: is_int($data['queryport'] ?? null) ? $data['queryport'] : null,
            online: is_bool($data['online'] ?? null) ? $data['online'] : false,
            gamename: is_string($data['gamename'] ?? null) ? $data['gamename'] : null,
            gameversion: is_string($data['gameversion'] ?? null) ? $data['gameversion'] : null,
            servertitle: is_string($data['servertitle'] ?? null) ? $data['servertitle'] : null,
            mapname: is_string($data['mapname'] ?? null) ? $data['mapname'] : null,
            gametype: is_string($data['gametype'] ?? null) ? $data['gametype'] : null,
            numplayers: is_int($data['numplayers'] ?? null) ? $data['numplayers'] : 0,
            maxplayers: is_int($data['maxplayers'] ?? null) ? $data['maxplayers'] : 0,
            rules: is_array($data['rules'] ?? null) ? $data['rules'] : [],
            players: is_array($data['players'] ?? null) ? $data['players'] : [],
            errstr: is_string($data['errstr'] ?? null) ? $data['errstr'] : null,
        );
    }
}
