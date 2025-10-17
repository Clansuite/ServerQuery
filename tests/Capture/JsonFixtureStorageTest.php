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
use Clansuite\Capture\ServerInfo;
use Clansuite\Capture\Storage\JsonFixtureStorage;
use PHPUnit\Framework\TestCase;

final class JsonFixtureStorageTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = \sys_get_temp_dir() . '/csq_fixtures_' . \uniqid();

        if (!\is_dir($this->tmpDir)) {
            \mkdir($this->tmpDir, 0o755, true);
        }
    }

    protected function tearDown(): void
    {
        // cleanup created files
        $files = \glob($this->tmpDir . '/*/*/*.json');

        if ($files !== false) {
            foreach ($files as $f) {
                @\unlink($f);
            }
        }
    }

    public function testSaveAndLoadRoundtrip(): void
    {
        $storage = new JsonFixtureStorage($this->tmpDir);

        $si     = new ServerInfo(address: '1.1.1.1', queryport: 27015, online: true);
        $result = new CaptureResult(['raw'], $si, ['meta' => true]);

        $path = $storage->save('TestProto', 'v1', '1.1.1.1', 27015, $result);

        $this->assertFileExists($path);

        $loaded = $storage->load('TestProto', 'v1', '1.1.1.1', 27015);

        $this->assertInstanceOf(CaptureResult::class, $loaded);
        $this->assertSame('1.1.1.1', $loaded->serverInfo->address);
        $this->assertSame(['raw'], $loaded->rawPackets);
    }

    public function testListAllReturnsArray(): void
    {
        $storage = new JsonFixtureStorage($this->tmpDir);

        // create one valid capture file by calling save
        $si     = new ServerInfo(address: '2.2.2.2', queryport: 27015, online: true);
        $result = new CaptureResult(['p1'], $si, ['ok' => true]);
        $storage->save('ProtoA', 'v2', '2.2.2.2', 27015, $result);

        // create a corrupt json file to exercise json_decode null branch
        $badDir = $this->tmpDir . '/prota/v2';

        if (!\is_dir($badDir)) {
            \mkdir($badDir, 0o755, true);
        }
        \file_put_contents($badDir . '/capture_3_3_3_3_11111.json', 'not a json');

        $list = $storage->listAll();

        $this->assertIsArray($list);
        $this->assertNotEmpty($list);
    }

    public function testBuildPathReflection(): void
    {
        $storage = new JsonFixtureStorage($this->tmpDir);
        $ref     = new ReflectionClass($storage);
        $m       = $ref->getMethod('buildPath');
        $m->setAccessible(true);

        $path = $m->invoke($storage, 'ProtoX', 'v9', '10.0.0.5', 12345);

        $this->assertStringContainsString('protox', $path);
        $this->assertStringContainsString('capture_10_0_0_5_12345.json', $path);
    }

    public function testLoadReturnsNullWhenFileMissing(): void
    {
        $storage = new JsonFixtureStorage($this->tmpDir);

        $res = $storage->load('NoProto', 'v0', '8.8.8.8', 11111);

        $this->assertNull($res);
    }

    public function testListAllOnEmptyDirReturnsEmpty(): void
    {
        $emptyDir = $this->tmpDir . '/empty';
        \mkdir($emptyDir, 0o755, true);

        $storage = new JsonFixtureStorage($emptyDir);

        $list = $storage->listAll();

        $this->assertIsArray($list);
        $this->assertCount(0, $list);
    }
}
