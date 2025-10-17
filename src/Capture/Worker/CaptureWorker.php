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

namespace Clansuite\Capture\Worker;

use function array_merge;
use function count;
use function usleep;
use Clansuite\ServerQuery\CSQuery;
use Clansuite\ServerQuery\Util\UdpClient;

/**
 * Worker class for performing server queries with timeout and retry logic.
 */
final readonly class CaptureWorker
{
    /**
     * Constructor.
     */
    public function __construct(
        private int $timeout = 5,
        private int $maxRetries = 2
    ) {
    }

    /**
     * Query a game server and return the results.
     *
     * @param string $protocol The protocol name (e.g., 'source', 'quake3')
     * @param string $ip       Server IP address
     * @param int    $port     Server port
     *
     * @return array{debug: array<mixed>, server_info: array<string,mixed>} Query results with debug info and server info
     */
    public function query(string $protocol, string $ip, int $port): array
    {
        $factory = new CSQuery;
        $server  = $factory->createInstance($protocol, $ip, $port);

        // Set timeout on the UDP client to prevent hanging
        $udpClient = new UdpClient;
        $udpClient->setTimeout($this->timeout);
        $server->setUdpClient($udpClient);

        // Initialize debug collection
        $allDebug = [];

        // Run a single query (may block); worker's lifetime is controlled by the parent process
        // First attempt: info + players
        $server->query_server(true, true);
        $allDebug = array_merge($allDebug, $server->debug);

        // If no players were returned, retry a players-only query a couple of times.
        // The parent process controls the worker timeout, so these retries are bounded.
        if (count($server->players) === 0) {
            for ($i = 0; $i < $this->maxRetries; $i++) {
                // try players-only (avoid re-fetching rules to be faster)
                $server->query_server(true, false);
                $allDebug = array_merge($allDebug, $server->debug);

                // small backoff before retrying
                usleep(200000); // 200ms
            }
        }

        return [
            'debug'       => $allDebug,
            'server_info' => [
                'address'     => $server->address,
                'queryport'   => $server->queryport,
                'online'      => $server->online,
                'gamename'    => $server->gamename,
                'gameversion' => $server->gameversion,
                'servertitle' => $server->servertitle,
                'mapname'     => $server->mapname,
                'gametype'    => $server->gametype,
                'numplayers'  => $server->numplayers,
                'maxplayers'  => $server->maxplayers,
                'rules'       => $server->rules,
                'players'     => $server->players,
                'errstr'      => $server->errstr,
            ],
        ];
    }
}
