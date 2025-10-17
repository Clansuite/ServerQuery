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
use Clansuite\Capture\Storage\FixtureStorageInterface;
use PHPUnit\Framework\TestCase;

class ListCapturesCommandTest extends TestCase
{
    public function testRunWithoutFilterPrintsAllCaptures(): void
    {
        $storage = $this->makeStorage([
            ['metadata' => ['protocol' => 'ddnet'], 'data' => ['a' => 1]],
            ['metadata' => ['protocol' => 'source'], 'data' => ['b' => 2]],
        ]);

        $cmd = new ListCapturesCommand($storage);

        ob_start();
        $code   = $cmd->run(['', 'list']);
        $output = (string) ob_get_clean();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('"protocol": "ddnet"', $output);
        $this->assertStringContainsString('"protocol": "source"', $output);
    }

    public function testRunWithListAndProtocolFilters(): void
    {
        $storage = $this->makeStorage([
            ['metadata' => ['protocol' => 'ddnet'], 'data' => ['a' => 1]],
            ['metadata' => ['protocol' => 'source'], 'data' => ['b' => 2]],
        ]);

        $cmd = new ListCapturesCommand($storage);

        ob_start();
        $code   = $cmd->run(['', 'list', 'ddnet']);
        $output = (string) ob_get_clean();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('"protocol": "ddnet"', $output);
        $this->assertStringNotContainsString('"protocol": "source"', $output);
    }

    public function testRunWithDirectProtocolArgFilters(): void
    {
        $storage = $this->makeStorage([
            ['metadata' => ['protocol' => 'ddnet'], 'data' => ['a' => 1]],
            ['metadata' => ['protocol' => 'source'], 'data' => ['b' => 2]],
        ]);

        $cmd = new ListCapturesCommand($storage);

        ob_start();
        $code   = $cmd->run(['', 'ddnet']);
        $output = (string) ob_get_clean();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('"protocol": "ddnet"', $output);
        $this->assertStringNotContainsString('"protocol": "source"', $output);
    }

    private function makeStorage(array $captures): FixtureStorageInterface
    {
        return new class($captures) implements FixtureStorageInterface
        {
            private array $captures;

            public function __construct(array $captures)
            {
                $this->captures = $captures;
            }

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
                return $this->captures;
            }
        };
    }
}
