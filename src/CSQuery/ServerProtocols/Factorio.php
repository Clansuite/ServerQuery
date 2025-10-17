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
use function file_get_contents;
use function is_array;
use function is_scalar;
use function json_decode;
use function stream_context_create;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Factorio server protocol implementation.
 *
 * Factorio uses HTTP API at https://multiplayer.factorio.com/get-game-details/{address}:{port}
 * Returns JSON with server information.
 *
 * @see https://wiki.factorio.com/Multiplayer
 */
class Factorio extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Factorio';

    /**
     * Protocol identifier.
     */
    public string $protocol = 'factorio';

    /**
     * Constructor.
     */
    public function __construct(mixed $address = null, mixed $queryport = null)
    {
        parent::__construct();
        $this->address   = $address !== null ? (string) $address : null;
        $this->queryport = $queryport !== null ? (int) $queryport : null;
    }

    /**
     * query method.
     */
    #[Override]
    public function query(ServerAddress $addr): ServerInfo
    {
        $info         = new ServerInfo;
        $info->online = false;

        if ($this->queryHTTP($addr, $info)) {
            $info->online = true;
        }

        return $info;
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
     * query_server method.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        // Use HTTP query
        $addr = new ServerAddress($this->address ?? '', $this->queryport ?? 0);
        $info = $this->query($addr);

        $this->online      = $info->online;
        $this->servertitle = $info->servertitle ?? '';
        $this->mapname     = $info->mapname ?? '';
        $this->numplayers  = $info->numplayers;
        $this->maxplayers  = $info->maxplayers;
        $this->gamename    = $info->gamename ?? '';
        $this->gameversion = $info->gameversion ?? '';
        $this->gametype    = $info->gametype ?? '';
        $this->password    = (int) ($info->password ?? false);

        if ($getPlayers && $info->players !== []) {
            $this->players = $info->players;
        }

        if ($getRules && $info->rules !== []) {
            $this->rules = $info->rules;
        }

        return $this->online;
    }

    private function queryHTTP(ServerAddress $addr, ServerInfo $info): bool
    {
        $host = $addr->ip;
        $port = $addr->port;
        $url  = "https://multiplayer.factorio.com/get-game-details/{$host}:{$port}";

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return false;
        }

        $data = json_decode($response, true);

        if ($data === null || !is_array($data)) {
            return false;
        }

        // Parse the JSON data
        $info->address     = $addr->ip . ':' . $addr->port;
        $info->queryport   = $addr->port;
        $info->gamename    = 'Factorio';
        $info->servertitle = (string) ($data['name'] ?? '');
        $info->mapname     = ''; // Factorio doesn't have maps
        $info->gametype    = ''; // No game type
        $playersData       = $data['players'] ?? [];
        $info->numplayers  = is_array($playersData) ? count($playersData) : 0;
        $info->maxplayers  = (int) ($data['max_players'] ?? 0);
        $info->password    = (bool) ($data['has_password'] ?? false);
        $appVer            = $data['application_version'] ?? [];

        if (is_array($appVer)) {
            $gameVer           = $appVer['game_version'] ?? '';
            $buildVer          = $appVer['build_version'] ?? '';
            $info->gameversion = (string) $gameVer . '.' . (string) $buildVer;
        } else {
            $info->gameversion = '';
        }

        // Players
        $players = [];

        if (isset($data['players']) && is_array($data['players'])) {
            foreach ($data['players'] as $name) {
                $players[] = ['name' => (string) $name, 'score' => 0, 'time' => 0];
            }
        }
        $info->players = $players;

        // Rules (server variables)
        $rules = [];

        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $rules[(string) $key] = (string) $value;
            } elseif (is_array($value) && $key === 'application_version') {
                $rules['game_version']  = (string) ($value['game_version'] ?? '');
                $rules['build_version'] = (string) ($value['build_version'] ?? '');
            }
        }
        $info->rules = $rules;

        return true;
    }
}
