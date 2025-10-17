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
use function is_array;
use function is_scalar;
use function json_decode;
use function preg_replace;
use function stream_context_create;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\ServerQuery\CSQuery;
use Override;

/**
 * Eco server protocol implementation.
 *
 * Eco uses HTTP API on the web port (usually game port +1).
 * The /info endpoint returns JSON with server information.
 *
 * @see https://eco.gamepedia.com/Server
 */
class Eco extends CSQuery implements ProtocolInterface
{
    /**
     * Protocol name.
     */
    public string $name = 'Eco';

    /**
     * Protocol identifier.
     */
    public string $protocol = 'eco';

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
        $url  = "http://{$host}:{$port}/info";

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
        $info->gamename    = 'Eco';
        $info->gameversion = (string) ($data['Version'] ?? '');
        $info->servertitle = $this->stripTags((string) ($data['Description'] ?? ''));
        $info->mapname     = (string) ($data['WorldSize'] ?? '');
        $info->gametype    = (string) ($data['Category'] ?? '');
        $info->numplayers  = (int) ($data['OnlinePlayers'] ?? 0);
        $info->maxplayers  = (int) ($data['TotalPlayers'] ?? 0);

        // Players
        $players = [];

        if (isset($data['OnlinePlayersNames']) && is_array($data['OnlinePlayersNames'])) {
            foreach ($data['OnlinePlayersNames'] as $name) {
                $players[] = ['name' => (string) $name, 'score' => 0, 'time' => 0];
            }
        }
        $info->players = $players;

        // Rules (server variables)
        $rules = [];

        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $rules[(string) $key] = (string) $value;
            }
        }
        $info->rules = $rules;

        return true;
    }

    private function stripTags(string $html): null|string
    {
        // Simple HTML tag stripping
        return preg_replace('/<[^>]*>/', '', $html);
    }
}
