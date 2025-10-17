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

namespace Clansuite\Capture\CLI;

use function ob_get_clean;
use function ob_start;
use Clansuite\Capture\CaptureResult;
use Clansuite\Capture\CaptureService;
use Clansuite\Capture\Extractor\VersionNormalizer;
use Clansuite\Capture\Protocol\ProtocolInterface;
use Clansuite\Capture\Protocol\ProtocolResolver;
use Clansuite\Capture\ServerAddress;
use Clansuite\Capture\ServerInfo;
use Clansuite\Capture\Storage\FixtureStorageInterface;
use Clansuite\Capture\Strategy\CaptureStrategyInterface;
use Exception;
use PHPUnit\Framework\TestCase;

class CaptureCommandTest extends TestCase
{
    public function testRunWithHelpFlagReturnsZeroAndPrintsHelp(): void
    {
        $service = $this->makeCaptureServiceReturning('/dev/null');

        $cmd = new CaptureCommand($service);

        // Capture stdout
        ob_start();
        $code   = $cmd->run(['', '--help']);
        $output = (string) ob_get_clean();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Clansuite Server Query - Capture Tool CLI', $output);
    }

    public function testRunWithMissingArgsPrintsMessageAndReturnsOne(): void
    {
        $service = $this->makeCaptureServiceReturning('/dev/null');

        $cmd = new CaptureCommand($service);

        ob_start();
        $code   = $cmd->run(['']);
        $output = (string) ob_get_clean();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Missing required arguments', $output);
    }

    public function testRunCallsCaptureServiceWithDefaultsAndPrintsPath(): void
    {
        $service = $this->makeCaptureServiceReturning('/tmp/capture.json');

        $cmd = new CaptureCommand($service);

        ob_start();
        $code   = $cmd->run(['', '127.0.0.1', '27015']);
        $output = (string) ob_get_clean();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('/tmp/capture.json', $output);
    }

    public function testRunHandlesCaptureExceptionAndReturnsOne(): void
    {
        $service = $this->makeCaptureServiceThrowing();

        $cmd = new CaptureCommand($service);

        // The capture strategy throws; CaptureCommand catches and calls error_log().
        // Expect the error_log output so PHPUnit does not treat the printed output as unexpected.
        $this->expectOutputRegex('/❌\s*boom/');

        $code = $cmd->run(['', '127.0.0.1', '27015']);

        $this->assertSame(1, $code);
    }

    private function makeCaptureServiceReturning(string $path): CaptureService
    {
        $proto = new class implements ProtocolInterface
        {
            public function query(ServerAddress $addr): ServerInfo
            {
                return new ServerInfo;
            }

            public function getProtocolName(): string
            {
                return 'testproto';
            }

            public function getVersion(ServerInfo $info): string
            {
                return '1.0';
            }
        };

        $className = $proto::class;

        $resolver = new ProtocolResolver(['source' => $className, 'auto' => $className]);

        $strategy = new class implements CaptureStrategyInterface
        {
            public function capture(ProtocolInterface $protocol, ServerAddress $addr, array $options): CaptureResult
            {
                return new CaptureResult([], new ServerInfo, []);
            }
        };

        $storage = new class($path) implements FixtureStorageInterface
        {
            private string $path;

            public function __construct(string $path)
            {
                $this->path = $path;
            }

            public function save(string $protocol, string $version, string $ip, int $port, CaptureResult $result): string
            {
                return $this->path;
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

        return new CaptureService($resolver, $strategy, $storage, $normalizer);
    }

    private function makeCaptureServiceThrowing(): CaptureService
    {
        $proto = new class implements ProtocolInterface
        {
            public function query(ServerAddress $addr): ServerInfo
            {
                return new ServerInfo;
            }

            public function getProtocolName(): string
            {
                return 'testproto';
            }

            public function getVersion(ServerInfo $info): string
            {
                return '1.0';
            }
        };

        $className = $proto::class;

        $resolver = new ProtocolResolver(['source' => $className, 'auto' => $className]);

        $strategy = new class implements CaptureStrategyInterface
        {
            public function capture(ProtocolInterface $protocol, ServerAddress $addr, array $options): CaptureResult
            {
                throw new Exception('boom');
            }
        };

        $storage = new class implements FixtureStorageInterface
        {
            public function save(string $protocol, string $version, string $ip, int $port, CaptureResult $result): string
            {
                return '/dev/null';
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

        return new CaptureService($resolver, $strategy, $storage, $normalizer);
    }
}
