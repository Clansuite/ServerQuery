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
use function is_int;
use function is_numeric;
use function is_string;
use function json_decode;
use function random_int;
use function stream_context_create;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Assetto Corsa protocol implementation.
 *
 * Uses HTTP requests to server endpoints.
 */
class AssettoCorsa extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Assetto Corsa';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Assetto Corsa'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'assettocorsa';

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct();

        if ($address !== null) {
            $this->address = $address;
        }

        if ($queryport !== null) {
            $this->queryport = $queryport;
        }
    }

    /**
     * query method.
     */
    #[Override]
    public function query(ServerAddress $addr): ServerInfo
    {
        $info         = new ServerInfo;
        $info->online = false;

        // Query server info
        $serverInfo = $this->queryEndpoint($addr, '/info');

        if ($serverInfo === null || $serverInfo === []) {
            return $info;
        }

        // Query car info
        $carInfo = $this->queryEndpoint($addr, '/JSON|' . random_int(0, 999999999999999));

        if ($carInfo === null || $carInfo === [] || !isset($carInfo['Cars'])) {
            return $info;
        }

        $this->parseServerInfo($serverInfo, $info);
        $this->parseCarInfo($carInfo, $info);
        $info->online = true;

        return $info;
    }

    /**
     * query_server method.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        $addr = new ServerAddress($this->address ?? '', $this->queryport ?? 0);
        $info = $this->query($addr);

        $this->online      = $info->online;
        $this->servertitle = $info->servertitle ?? '';
        $this->mapname     = $info->mapname ?? '';
        $this->numplayers  = $info->numplayers;
        $this->maxplayers  = $info->maxplayers;
        $this->players     = [];

        foreach ($info->players as $player) {
            if (is_array($player)) {
                $this->players[] = [
                    'name'  => $player['name'] ?? '',
                    'score' => 0,
                    'time'  => 0,
                ];
            }
        }
        $this->numplayers = count($this->players);

        return $this->online;
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
        return $info->version ?? 'unknown';
    }

    /**
     * Query a HTTP endpoint and return a decoded JSON array.
     *
     * @return null|array<string,mixed>
     */
    private function queryEndpoint(ServerAddress $addr, string $path): ?array
    {
        $url     = "http://{$addr->ip}:{$addr->port}{$path}";
        $context = stream_context_create([
            'http' => [
                'timeout'    => 5,
                'user_agent' => 'Clansuite-GameServer-Query/1.0',
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $result = json_decode($response, true);

        // Ensure we have an associative array with string keys
        if ($result === null || !is_array($result)) {
            return null;
        }

        // Normalize keys: if array has non-string keys, try to cast to string-indexed array
        $assoc = [];

        foreach ($result as $k => $v) {
            if (!is_string($k)) {
                // cast numeric keys to string to match signature
                $k = (string) $k;
            }
            $assoc[$k] = $v;
        }

        return $assoc;
    }

    /**
     * @param array<string, mixed> $serverInfo
     */
    private function parseServerInfo(array $serverInfo, ServerInfo $info): void
    {
        // Parse server info with type checks
        $name              = $serverInfo['name'] ?? null;
        $info->servertitle = is_string($name) ? $name : '';

        $track         = $serverInfo['track'] ?? null;
        $info->mapname = is_string($track) ? $track : '';

        $clients          = $serverInfo['clients'] ?? null;
        $info->numplayers = is_int($clients) ? $clients : (is_numeric($clients) ? (int) $clients : 0);

        $maxclients       = $serverInfo['maxclients'] ?? null;
        $info->maxplayers = is_int($maxclients) ? $maxclients : (is_numeric($maxclients) ? (int) $maxclients : 0);

        $poweredBy         = $serverInfo['poweredBy'] ?? null;
        $info->gameversion = is_string($poweredBy) ? $poweredBy : '';

        // Password not directly available, perhaps set in rules
        $pass = $serverInfo['pass'] ?? null;

        if ($pass === true || $pass === 1 || $pass === '1' || $pass === 'true') {
            $info->rules['password'] = true;
        }
    }

    /**
     * @param array<string, mixed> $carInfo
     */
    private function parseCarInfo(array $carInfo, ServerInfo $info): void
    {
        // Parse car info
        $info->players = [];

        $cars = $carInfo['Cars'] ?? null;

        if (is_array($cars)) {
            foreach ($cars as $car) {
                if (!is_array($car)) {
                    continue;
                }

                $isConnected = $car['IsConnected'] ?? null;

                if ($isConnected === true || $isConnected === 1 || $isConnected === '1' || $isConnected === 'true') {
                    $driver = $car['DriverName'] ?? null;
                    $name   = is_string($driver) ? $driver : '';

                    $info->players[] = [
                        'name'  => $name,
                        'score' => 0,
                        'time'  => 0,
                    ];
                }
            }
        }

        // If server info has clients, use that, else count
        if ($info->numplayers === 0) {
            $info->numplayers = count($info->players);
        }
    }
}
