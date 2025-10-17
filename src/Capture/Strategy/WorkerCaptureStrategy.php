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

namespace Clansuite\Capture\Strategy;

use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function time;
use Clansuite\Capture\CaptureResult;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\Capture\Worker\CaptureWorker;
use Override;
use Throwable;

/**
 * Capture strategy that uses worker processes to perform server queries asynchronously.
 */
final readonly class WorkerCaptureStrategy implements CaptureStrategyInterface
{
    /**
     * Constructor.
     */
    public function __construct(private int $timeout = 10)
    {
    }

    /**
     * capture method.
     *
     * @param array<mixed> $options
     */
    #[Override]
    public function capture(ProtocolInterface $protocol, ServerAddress $addr, array $options): CaptureResult
    {
        $protocolName = isset($options['protocol_name']) && is_string($options['protocol_name']) ? $options['protocol_name'] : 'source'; // fallback to source if not provided

        // Create worker instance with timeout configuration
        $worker = new CaptureWorker($this->timeout);

        try {
            // Query the server directly using the worker
            /** @var array{debug: array<mixed>, server_info: array<string, mixed>} $workerData */
            $workerData = $worker->query($protocolName, $addr->ip, $addr->port);

            $serverInfoData = $workerData['server_info'];

            $serverInfo = new ServerInfo(
                address: isset($serverInfoData['address']) && is_string($serverInfoData['address']) ? $serverInfoData['address'] : null,
                queryport: isset($serverInfoData['queryport']) && is_int($serverInfoData['queryport']) ? $serverInfoData['queryport'] : null,
                online: isset($serverInfoData['online']) && is_bool($serverInfoData['online']) ? $serverInfoData['online'] : false,
                gamename: isset($serverInfoData['gamename']) && is_string($serverInfoData['gamename']) ? $serverInfoData['gamename'] : null,
                gameversion: isset($serverInfoData['gameversion']) && is_string($serverInfoData['gameversion']) ? $serverInfoData['gameversion'] : null,
                servertitle: isset($serverInfoData['servertitle']) && is_string($serverInfoData['servertitle']) ? $serverInfoData['servertitle'] : null,
                mapname: isset($serverInfoData['mapname']) && is_string($serverInfoData['mapname']) ? $serverInfoData['mapname'] : null,
                gametype: isset($serverInfoData['gametype']) && is_string($serverInfoData['gametype']) ? $serverInfoData['gametype'] : null,
                numplayers: isset($serverInfoData['numplayers']) && is_int($serverInfoData['numplayers']) ? $serverInfoData['numplayers'] : 0,
                maxplayers: isset($serverInfoData['maxplayers']) && is_int($serverInfoData['maxplayers']) ? $serverInfoData['maxplayers'] : 0,
                rules: isset($serverInfoData['rules']) && is_array($serverInfoData['rules']) ? $serverInfoData['rules'] : [],
                players: isset($serverInfoData['players']) && is_array($serverInfoData['players']) ? $serverInfoData['players'] : [],
                errstr: isset($serverInfoData['errstr']) && is_string($serverInfoData['errstr']) ? $serverInfoData['errstr'] : null,
            );

            $debugLog = $workerData['debug'];

            if (!is_array($debugLog)) {
                $debugLog = [];
            }

            $metadata = [
                'ip'          => $addr->ip,
                'port'        => $addr->port,
                'protocol'    => $protocol->getProtocolName(),
                'timestamp'   => time(),
                'worker_used' => true,
            ];

            return new CaptureResult($debugLog, $serverInfo, $metadata);
        } catch (Throwable $e) {
            // If worker fails, return a failed result
            $serverInfo = new ServerInfo(
                address: $addr->ip,
                queryport: $addr->port,
                online: false,
                errstr: $e->getMessage(),
            );

            $metadata = [
                'ip'          => $addr->ip,
                'port'        => $addr->port,
                'protocol'    => $protocol->getProtocolName(),
                'timestamp'   => time(),
                'worker_used' => true,
                'error'       => $e->getMessage(),
            ];

            return new CaptureResult([], $serverInfo, $metadata);
        }
    }
}
