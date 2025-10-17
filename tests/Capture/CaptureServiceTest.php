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

use Clansuite\Capture\CaptureResult;
use Clansuite\Capture\CaptureService;
use Clansuite\Capture\Extractor\VersionNormalizer;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\Protocol\ProtocolResolver;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\Capture\Storage\FixtureStorageInterface;
use Clansuite\Capture\Strategy\CaptureStrategyInterface;
use PHPUnit\Framework\TestCase;

class TestProtocolForCaptureService implements ProtocolInterface
{
    public function query(ServerAddress $addr): ServerInfo
    {
        return new ServerInfo(address: $addr->ip, queryport: $addr->port, online: true);
    }

    public function getProtocolName(): string
    {
        return 'protoX';
    }

    public function getVersion(ServerInfo $info): string
    {
        return '1.2.3';
    }
}

final class CaptureServiceTest extends TestCase
{
    public function testCaptureInvokesDependenciesAndReturnsPath(): void
    {
        // ProtocolResolver::detectProtocol() returns 'source' by default, so include it in the map
        $resolver = new ProtocolResolver(['source' => TestProtocolForCaptureService::class]);

        $strategy = new class implements CaptureStrategyInterface
        {
            public function capture(ProtocolInterface $protocol, ServerAddress $addr, array $options): CaptureResult
            {
                $si = new ServerInfo(address: $addr->ip, queryport: $addr->port, online: true);

                return new CaptureResult(['p'], $si, ['o' => $options]);
            }
        };

        $tmp = \sys_get_temp_dir() . '/csq_capture_' . \uniqid();
        \mkdir($tmp, 0o755, true);

        $storage = new class($tmp) implements FixtureStorageInterface
        {
            private string $dir;

            public function __construct(string $d)
            {
                $this->dir = $d;
            }

            public function save(string $protocol, string $version, string $ip, int $port, CaptureResult $result): string
            {
                $path = $this->dir . '/' . \strtolower($protocol) . '_' . $ip . '_' . $port . '.json';
                \file_put_contents($path, \json_encode(['ok' => true]));

                return $path;
            }

            public function load(string $protocol, string $version, string $ip, int $port): ?CaptureResult
            {
                return null;
            }

            public function listAll(): array
            {
                return [];
            }
        };

        $normalizer = new VersionNormalizer;

        $service = new CaptureService($resolver, $strategy, $storage, $normalizer);

        $path = $service->capture('9.9.9.9', 9999, 'auto', ['x' => 1]);

        $this->assertStringContainsString('protox', $path);
        $this->assertFileExists($path);
    }
}
