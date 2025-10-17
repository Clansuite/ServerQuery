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

namespace Tests\CSQuery\ServerProtocols;

use function count;
use function file_get_contents;
use function is_array;
use function json_decode;
use Clansuite\ServerQuery\CSQuery;
use Clansuite\ServerQuery\Util\MockUdpClient;
use PHPUnit\Framework\TestCase;

/**
 * Test parsing of Ark Survival Evolved fixtures using replay testing.
 */
class ArkSurvivalEvolvedReplayTest extends TestCase
{
    private string $fixtureBaseDir = __DIR__ . '/../../fixtures/arksurvivalevolved';

    /**
     * Test replay parsing of the v1.0.0.0 fixture.
     */
    public function testReplayV1000(): void
    {
        $jsonFile = $this->fixtureBaseDir . '/v1_0_0_0/capture_176_31_226_111_27015.json';

        $this->runFixtureReplayTest($jsonFile);
    }

    /**
     * Run a replay test using the specified fixture file.
     */
    private function runFixtureReplayTest(string $jsonFile): void
    {
        // Load metadata
        $this->assertFileExists($jsonFile, 'JSON metadata file should exist');
        $metadata = json_decode(file_get_contents($jsonFile), true);
        $this->assertIsArray($metadata, 'JSON metadata should decode to an array');

        // Check that we have server info
        $this->assertArrayHasKey('server_info', $metadata, 'Metadata must contain server_info');
        $expectedServerInfo = $metadata['server_info'];

        // Create mock UDP client and load fixture
        $mockUdpClient = new MockUdpClient;
        $this->assertTrue($mockUdpClient->loadFixture($jsonFile), 'Should load fixture successfully');

        // Create server instance
        $factory = new CSQuery;
        $server  = $factory->createInstance('Arkse', '127.0.0.1', 27015);

        // Inject mock client
        $server->setUdpClient($mockUdpClient);

        // Run the query
        $result = $server->query_server(true, true);

        // For offline servers, we might not get a result, but we should still validate what we can
        if (!$result && empty($expectedServerInfo['online'])) {
            // Server was offline during capture, test that parsing handles this gracefully
            $this->assertFalse($result, 'Query should fail for offline server');

            return;
        }

        // Validate that the server parsed the expected data
        $this->assertEquals($expectedServerInfo['gamename'] ?? '', $server->gamename ?? '', 'Game name should match');
        $this->assertEquals($expectedServerInfo['servertitle'] ?? '', $server->servertitle ?? '', 'Server title should match');
        $this->assertEquals($expectedServerInfo['mapname'] ?? '', $server->mapname ?? '', 'Map name should match');
        $this->assertEquals($expectedServerInfo['numplayers'] ?? 0, $server->numplayers ?? 0, 'Player count should match');
        $this->assertEquals($expectedServerInfo['maxplayers'] ?? 0, $server->maxplayers ?? 0, 'Max players should match');

        // Validate rules if present
        if (isset($expectedServerInfo['rules']) && is_array($expectedServerInfo['rules'])) {
            foreach ($expectedServerInfo['rules'] as $key => $expectedValue) {
                $this->assertEquals($expectedValue, $server->rules[$key] ?? null, "Rule '{$key}' should match");
            }
        }

        // Validate players if present
        if (isset($expectedServerInfo['players']) && is_array($expectedServerInfo['players'])) {
            $this->assertIsArray($server->players, 'Players should be an array');
            $this->assertCount(count($expectedServerInfo['players']), $server->players, 'Player count should match expected');

            foreach ($expectedServerInfo['players'] as $index => $expectedPlayer) {
                if (isset($server->players[$index])) {
                    $actualPlayer = $server->players[$index];

                    foreach ($expectedPlayer as $playerKey => $playerValue) {
                        $this->assertEquals($playerValue, $actualPlayer[$playerKey] ?? null, "Player {$index} {$playerKey} should match");
                    }
                }
            }
        }
    }
}
