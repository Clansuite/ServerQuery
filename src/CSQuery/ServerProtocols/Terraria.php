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

use function chr;
use function explode;
use function fclose;
use function file_get_contents;
use function fread;
use function fsockopen;
use function fwrite;
use function is_array;
use function json_decode;
use function pack;
use function preg_match;
use function str_ends_with;
use function str_starts_with;
use function stream_context_create;
use function strpos;
use function substr;
use function trim;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Terraria protocol implementation.
 *
 * Uses native TCP protocol with TShock REST API fallback.
 */
class Terraria extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Terraria';

    /**
     * List of supported games.
     *
     * @var array<string>
     */
    public array $supportedGames = ['Terraria'];

    /**
     * Protocol identifier.
     */
    public string $protocol = 'terraria';

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct();
        $this->address   = $address;
        $this->queryport = $queryport;
    }

    /**
     * query method.
     */
    #[Override]
    public function query(ServerAddress $addr): ServerInfo
    {
        // Implement TCP query
        $info         = new ServerInfo;
        $info->online = false;

        // Try TShock API first
        if ($this->queryTShockAPI($addr, $info)) {
            return $info;
        }

        // Fallback to native TCP protocol
        return $this->queryNativeTCP($addr, $info);
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
     * query_server method.
     */
    #[Override]
    public function query_server(bool $getPlayers = true, bool $getRules = true): bool
    {
        // Use TCP query
        $addr = new ServerAddress($this->address ?? '', $this->queryport ?? 0);
        $info = $this->query($addr);

        $this->online      = $info->online;
        $this->servertitle = $info->name ?? '';
        $this->mapname     = $info->map ?? '';
        $this->numplayers  = $info->players_current ?? 0;
        $this->maxplayers  = $info->players_max ?? 0;
        $this->players     = [];

        foreach ($info->players as $player) {
            if (is_array($player)) {
                $this->players[] = [
                    'name'  => (string) ($player['name'] ?? ''),
                    'score' => (int) ($player['score'] ?? 0),
                    'time'  => (int) ($player['time'] ?? 0),
                ];
            }
        }

        return $this->online;
    }

    private function queryTShockAPI(ServerAddress $addr, ServerInfo $info): bool
    {
        // TShock REST API on port 7878 (game port + 101)
        $host      = $addr->ip;
        $apiPort   = $addr->port + 101;
        $endpoints = [
            "http://{$host}:{$apiPort}/v2/server/status",
            "http://{$host}:{$apiPort}/status",
            "http://{$host}:{$apiPort}/v3/server/status",
        ];

        foreach ($endpoints as $url) {
            $context = stream_context_create([
                'http' => [
                    'timeout'    => 5,
                    'user_agent' => 'Clansuite-Query/1.0',
                ],
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response !== false) {
                // Check if it's HTTP 200 and ends with }
                if (str_starts_with($response, 'HTTP/1.1 200') && str_ends_with(trim($response), '}')) {
                    $jsonStart = strpos($response, '{');

                    if ($jsonStart !== false) {
                        $jsonBody = substr($response, $jsonStart);
                        $data     = json_decode($jsonBody, true);

                        if ($data !== null && isset($data['status']) && $data['status'] === '200') {
                            $this->parseTShockResponse($data, $info);
                            $info->online = true;

                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param array<mixed> $data
     */
    private function parseTShockResponse(array $data, ServerInfo $info): void
    {
        $server = $data['server'] ?? null;

        if (is_array($server)) {
            $info->name            = (string) ($server['name'] ?? '');
            $info->map             = (string) ($server['world'] ?? '');
            $info->players_current = (int) ($server['players'] ?? 0);
            $info->players_max     = (int) ($server['maxplayers'] ?? 0);
            $info->version         = (string) ($server['version'] ?? '');
        }

        $playersData = $data['players'] ?? null;

        if (is_array($playersData)) {
            $info->players = [];

            foreach ($playersData as $player) {
                if (is_array($player)) {
                    $info->players[] = [
                        'name'  => (string) ($player['name'] ?? ''),
                        'score' => (int) ($player['score'] ?? 0),
                        'time'  => (int) ($player['time'] ?? 0),
                    ];
                }
            }
        }
    }

    private function queryNativeTCP(ServerAddress $addr, ServerInfo $info): ServerInfo
    {
        $socket = $this->openSocket($addr->ip, $addr->port, 5);

        if ($socket === false) {
            return $info;
        }

        // Send server info request packet
        $packet = pack('V', 5) . chr(1); // Length 5, type 1
        fwrite($socket, $packet);

        // Read response
        $response = fread($socket, 4096);
        fclose($socket);

        if ($response !== false) {
            $this->parseNativeResponse($response, $info);
            $info->online = true;
        }

        return $info;
    }

    private function parseNativeResponse(string $response, ServerInfo $info): void
    {
        // Terraria native protocol parsing
        // This is simplified; full implementation would need proper packet parsing
        $lines = explode("\n", trim($response));

        foreach ($lines as $line) {
            if (str_starts_with($line, 'Server name: ')) {
                $info->name = substr($line, 12);
            } elseif (str_starts_with($line, 'Map: ')) {
                $info->map = substr($line, 5);
            } elseif (preg_match('/Players: (\d+)\/(\d+)/', $line, $matches) !== false) {
                $info->players_current = (int) ($matches[1] ?? 0);
                $info->players_max     = (int) ($matches[2] ?? 0);
            }
        }
    }

    /**
     * Open a socket while keeping errno/errstr as variables (avoids passing expressions by reference).
     *
     * @return false|resource
     *
     * @phpstan-ignore typeCoverage.returnTypeCoverage
     */
    private function openSocket(string $host, int $port, int $timeout)
    {
        $tmpErrno  = 0;
        $tmpErrstr = '';

        return @fsockopen($host, $port, $tmpErrno, $tmpErrstr, $timeout);
    }
}
