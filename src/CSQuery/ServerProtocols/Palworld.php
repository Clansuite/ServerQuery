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

use const JSON_ERROR_NONE;
use function base64_encode;
use function file_get_contents;
use function is_array;
use function json_decode;
use function json_last_error;
use function stream_context_create;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Palworld server protocol implementation.
 *
 * Palworld uses REST API with basic authentication.
 * Default port is 8212.
 *
 * @see https://docs.palworldgame.com/
 */
class Palworld extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Palworld';

    /**
     * Protocol identifier.
     */
    public string $protocol = 'palworld';

    /**
     * Constructor.
     */
    public function __construct(?string $address = null, ?int $queryport = null)
    {
        parent::__construct($address, $queryport);
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

    private function queryHTTP(ServerAddress $addr, ServerInfo $info): bool
    {
        $host = $addr->ip;
        $port = $addr->port;

        // Try to get server info
        $infoData = $this->makeAPIRequest($host, $port, 'info');

        if ($infoData === null || $infoData === []) {
            return false;
        }

        // Try to get players
        $playersData = $this->makeAPIRequest($host, $port, 'players');

        // Try to get metrics
        $metricsData = $this->makeAPIRequest($host, $port, 'metrics');

        // Parse the data
        $info->address     = $host . ':' . $port;
        $info->queryport   = $port;
        $info->gamename    = 'Palworld';
        $info->servertitle = (string) ($infoData['servername'] ?? '');
        $info->mapname     = ''; // Palworld doesn't have traditional maps
        $info->gametype    = '';
        $info->gameversion = (string) ($infoData['version'] ?? '');

        // Get player count from metrics if available
        if ($metricsData !== null && $metricsData !== []) {
            $info->numplayers = (int) ($metricsData['currentplayernum'] ?? 0);
            $info->maxplayers = (int) ($metricsData['maxplayernum'] ?? 0);
        } else {
            $info->numplayers = 0;
            $info->maxplayers = 0;
        }

        $info->password = false; // Assume no password for now

        // Parse players
        if ($playersData !== null && isset($playersData['players']) && is_array($playersData['players'])) {
            $info->players = [];

            foreach ($playersData['players'] as $player) {
                if (is_array($player)) {
                    $info->players[] = [
                        'name'  => (string) ($player['name'] ?? ''),
                        'score' => 0, // Palworld doesn't have scores
                        'time'  => 0, // Palworld doesn't track play time in API
                    ];
                }
            }
        }

        // Add server rules/settings
        $info->rules = [];

        $info->rules['version']     = $infoData['version'] ?? '';
        $info->rules['servername']  = $infoData['servername'] ?? '';
        $info->rules['description'] = $infoData['description'] ?? '';

        return true;
    }

    /**
     * @return ?array<mixed>
     */
    private function makeAPIRequest(string $host, int $port, string $endpoint): ?array
    {
        $url = "http://{$host}:{$port}/v1/api/{$endpoint}";

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'header'  => 'Authorization: Basic ' . base64_encode('admin:admin'), // Default credentials
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return is_array($data) ? $data : null;
    }
}
