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

namespace Tests\CSQuery;

use function array_map;
use function file_put_contents;
use function glob;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use Clansuite\ServerQuery\DocumentProtocols;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DocumentProtocolsTest extends TestCase
{
    private $oldArgv;
    private string $fakeFile;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/cp_test_' . uniqid();
        mkdir($this->tmpDir);

        // Create a fake protocol class file with a unique class name to avoid redeclare across tests
        $unique  = 'FakeProto_' . uniqid();
        $content = '<?php declare(strict_types=1);' . "\n";
        $content .= 'namespace Clansuite\\ServerQuery\\ServerProtocols;' . "\n";
        $content .= 'use Clansuite\\ServerQuery\\CSQuery;' . "\n\n";
        $content .= 'class ' . $unique . ' extends CSQuery' . "\n";
        $content .= "{\n";
        $content .= "    public string \$name = 'Fake Game';\n";
        $content .= "    public array \$supportedGames = ['Fake Game'];\n";
        $content .= "    public array \$game_series_list = ['Fake Series'];\n";
        $content .= "    public string \$protocol = '{$unique}';\n";
        $content .= "}\n";

        $file = $this->tmpDir . '/' . $unique . '.php';
        file_put_contents($file, $content);
        $this->fakeFile = $file;
        // Ensure $argv is defined to avoid warnings in the included source file
        $this->oldArgv   = $GLOBALS['argv'] ?? null;
        $GLOBALS['argv'] = ['phpunit'];

        // Make sure the fake class is declared for class_exists checks
        require_once $this->fakeFile;
    }

    protected function tearDown(): void
    {
        // delete all files in the temp directory
        array_map('unlink', glob($this->tmpDir . '/*.*'));

        rmdir($this->tmpDir);

        if ($this->oldArgv === null) {
            unset($GLOBALS['argv']);
        } else {
            $GLOBALS['argv'] = $this->oldArgv;
        }
    }

    public function testParseAndRender(): void
    {
        $doc = new DocumentProtocols($this->tmpDir);

        $doc->parseProtocols();

        $md = $doc->renderMarkdown();
        $this->assertStringContainsString('Clansuite Server Query', $md);
        $this->assertStringContainsString('Total Protocols', $md);

        $html = $doc->renderHtml();
        $this->assertStringContainsString('<h2>Clansuite Server Query</h2>', $html);
        $this->assertStringContainsString('Fake Game', $html);

        $games = $doc->getSupportedGames();
        $this->assertContains('Fake Game', $games);

        $series = $doc->getGameSeries();
        $this->assertContains('Fake Series', $series);
    }

    public function testWriteFilesCreatesOutputs(): void
    {
        $outDir = $this->tmpDir . '/out';
        mkdir($outDir);

        $doc = new DocumentProtocols($this->tmpDir);
        $doc->parseProtocols();

        // Expect the printed messages so PHPUnit does not mark the test as risky
        $this->expectOutputRegex('/Generated protocols\.md and protocols\.html\n/');

        $doc->writeFiles($outDir);

        $this->assertFileExists($outDir . '/protocols.md');
        $this->assertFileExists($outDir . '/protocols.html');

        // Clean up
        unlink($outDir . '/protocols.md');
        unlink($outDir . '/protocols.html');
        rmdir($outDir);
    }

    public function testGetBaseProtocolKnownMappings(): void
    {
        $doc    = new DocumentProtocols($this->tmpDir);
        $method = new ReflectionMethod(DocumentProtocols::class, 'getBaseProtocol');
        $method->setAccessible(true);

        $this->assertSame('Steam', $method->invoke($doc, 'A2S'));
        $this->assertSame('Half-Life', $method->invoke($doc, 'Halflife'));
        $this->assertSame('Quake 3', $method->invoke($doc, 'Quake3'));
        $this->assertSame('Assetto Corsa', $method->invoke($doc, 'assettocorsa'));
        $this->assertSame('Steam', $method->invoke($doc, 'source'));
    }
}
