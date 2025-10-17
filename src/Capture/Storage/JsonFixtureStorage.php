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

namespace Clansuite\Capture\Storage;

use const GLOB_ONLYDIR;
use const JSON_PRETTY_PRINT;
use function base64_decode;
use function base64_encode;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function is_array;
use function is_bool;
use function is_dir;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;
use function mkdir;
use function serialize;
use function sprintf;
use function str_replace;
use function strtolower;
use function unserialize;
use Clansuite\Capture\CaptureResult;
use Clansuite\Capture\ServerInfo;
use Override;

/**
 * Stores and retrieves capture results as JSON fixtures for testing and development purposes.
 */
final readonly class JsonFixtureStorage implements FixtureStorageInterface
{
    /**
     * Constructor.
     */
    public function __construct(private string $fixturesDir)
    {
    }

    /**
     * save method.
     */
    #[Override]
    public function save(string $protocol, string $version, string $ip, int $port, CaptureResult $result): string
    {
        $path = $this->buildPath($protocol, $version, $ip, $port);
        $dir  = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }
        $data = [
            'metadata'    => $result->metadata,
            'packets'     => base64_encode(serialize($result->rawPackets)), // Assuming rawPackets is array of packets
            'server_info' => $result->serverInfo->toArray(),
        ];
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

        return $path;
    }

    /**
     * load method.
     */
    #[Override]
    public function load(string $protocol, string $version, string $ip, int $port): ?CaptureResult
    {
        $path = $this->buildPath($protocol, $version, $ip, $port);

        if (!file_exists($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $data = json_decode($contents, true);

        if (!is_array($data)) {
            return null;
        }

        $serverInfoData = $data['server_info'] ?? null;

        if (!is_array($serverInfoData)) {
            return null;
        }

        $encodedPackets = $data['packets'] ?? null;

        if (!is_string($encodedPackets)) {
            return null;
        }

        $decodedPackets = base64_decode($encodedPackets, true);

        if ($decodedPackets === false) {
            return null;
        }

        $rawPackets = @unserialize($decodedPackets);

        if ($rawPackets === false && $decodedPackets !== serialize(false)) {
            return null;
        }

        $metadata = $data['metadata'] ?? [];

        if (!is_array($metadata)) {
            $metadata = [];
        }

        $serverInfoData = [
            'address'         => isset($serverInfoData['address']) && is_string($serverInfoData['address']) ? $serverInfoData['address'] : null,
            'queryport'       => isset($serverInfoData['queryport']) && is_int($serverInfoData['queryport']) ? $serverInfoData['queryport'] : null,
            'online'          => isset($serverInfoData['online']) && is_bool($serverInfoData['online']) ? $serverInfoData['online'] : false,
            'gamename'        => isset($serverInfoData['gamename']) && is_string($serverInfoData['gamename']) ? $serverInfoData['gamename'] : null,
            'gameversion'     => isset($serverInfoData['gameversion']) && is_string($serverInfoData['gameversion']) ? $serverInfoData['gameversion'] : null,
            'servertitle'     => isset($serverInfoData['servertitle']) && is_string($serverInfoData['servertitle']) ? $serverInfoData['servertitle'] : null,
            'mapname'         => isset($serverInfoData['mapname']) && is_string($serverInfoData['mapname']) ? $serverInfoData['mapname'] : null,
            'gametype'        => isset($serverInfoData['gametype']) && is_string($serverInfoData['gametype']) ? $serverInfoData['gametype'] : null,
            'numplayers'      => isset($serverInfoData['numplayers']) && is_int($serverInfoData['numplayers']) ? $serverInfoData['numplayers'] : 0,
            'maxplayers'      => isset($serverInfoData['maxplayers']) && is_int($serverInfoData['maxplayers']) ? $serverInfoData['maxplayers'] : 0,
            'rules'           => isset($serverInfoData['rules']) && is_array($serverInfoData['rules']) ? $serverInfoData['rules'] : [],
            'players'         => isset($serverInfoData['players']) && is_array($serverInfoData['players']) ? $serverInfoData['players'] : [],
            'channels'        => isset($serverInfoData['channels']) && is_array($serverInfoData['channels']) ? $serverInfoData['channels'] : [],
            'errstr'          => isset($serverInfoData['errstr']) && is_string($serverInfoData['errstr']) ? $serverInfoData['errstr'] : null,
            'password'        => isset($serverInfoData['password']) && is_bool($serverInfoData['password']) ? $serverInfoData['password'] : null,
            'name'            => isset($serverInfoData['name']) && is_string($serverInfoData['name']) ? $serverInfoData['name'] : null,
            'map'             => isset($serverInfoData['map']) && is_string($serverInfoData['map']) ? $serverInfoData['map'] : null,
            'players_current' => isset($serverInfoData['players_current']) && is_int($serverInfoData['players_current']) ? $serverInfoData['players_current'] : null,
            'players_max'     => isset($serverInfoData['players_max']) && is_int($serverInfoData['players_max']) ? $serverInfoData['players_max'] : null,
            'version'         => isset($serverInfoData['version']) && is_string($serverInfoData['version']) ? $serverInfoData['version'] : null,
            'motd'            => isset($serverInfoData['motd']) && is_string($serverInfoData['motd']) ? $serverInfoData['motd'] : null,
        ];

        $serverInfo = new ServerInfo(...$serverInfoData);

        if (!is_array($rawPackets)) {
            $rawPackets = [];
        }

        return new CaptureResult($rawPackets, $serverInfo, $metadata);
    }

    /**
     * listAll method.
     *
     * @return array<mixed>
     */
    #[Override]
    public function listAll(): array
    {
        $captures     = [];
        $protocolDirs = glob($this->fixturesDir . '/*', GLOB_ONLYDIR);

        if ($protocolDirs === false) {
            $protocolDirs = [];
        }

        foreach ($protocolDirs as $protocolDir) {
            $versionDirs = glob($protocolDir . '/*', GLOB_ONLYDIR);

            if ($versionDirs === false) {
                $versionDirs = [];
            }

            foreach ($versionDirs as $versionDir) {
                $files = glob($versionDir . '/*.json');

                if ($files === false) {
                    $files = [];
                }

                foreach ($files as $file) {
                    $c = file_get_contents($file);

                    if ($c === false) {
                        continue;
                    }

                    $data = json_decode($c, true);

                    if (is_array($data)) {
                        $captures[] = $data;
                    }
                }
            }
        }

        return $captures;
    }

    private function buildPath(string $protocol, string $version, string $ip, int $port): string
    {
        $normalizedIp = str_replace('.', '_', $ip);
        $filename     = sprintf('capture_%s_%d.json', $normalizedIp, $port);

        return $this->fixturesDir . '/' . strtolower($protocol) . '/' . $version . '/' . $filename;
    }
}
