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

use function file_get_contents;
use function simplexml_load_string;
use function stream_context_create;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Farming Simulator server protocol implementation.
 *
 * Farming Simulator uses HTTP API with XML response.
 * Requires a token from the server settings page.
 *
 * @see https://github.com/ich777/farming-simulator-dedicated-server
 */
class FarmingSimulator extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'FarmingSimulator';

    /**
     * Protocol identifier.
     */
    public string $protocol = 'farmingsimulator';

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
        // Note: token is required, but for example, we assume it's provided or use default
        $token = 'example_token'; // In real use, get from server settings
        $url   = "http://{$host}:{$port}/feed/dedicated-server-stats.xml?code={$token}";

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return false;
        }

        $xml = simplexml_load_string($response);

        if ($xml === false) {
            return false;
        }

        // Parse the XML data
        $info->address     = $addr->ip . ':' . $addr->port;
        $info->queryport   = $addr->port;
        $info->gamename    = 'Farming Simulator';
        $info->servertitle = (string) $xml->Server['name'];
        $info->mapname     = (string) $xml->Server['mapName'];
        $info->numplayers  = (int) $xml->Server->Slots['numUsed'];
        $info->maxplayers  = (int) $xml->Server->Slots['capacity'];

        // Players
        $players = [];

        foreach ($xml->Server->Slots->Player as $player) {
            if ((string) $player['isUsed'] === 'true') {
                $players[] = ['name' => (string) $player, 'score' => 0, 'time' => (int) $player['uptime']];
            }
        }
        $info->players = $players;

        // Rules (server variables)
        $rules = [];

        foreach ($xml->Server->attributes() as $key => $value) {
            $rules[$key] = (string) $value;
        }
        $info->rules = $rules;

        return true;
    }
}
