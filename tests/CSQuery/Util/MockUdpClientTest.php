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

use Clansuite\ServerQuery\Util\MockUdpClient;
use PHPUnit\Framework\TestCase;

final class MockUdpClientTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = \sys_get_temp_dir() . '/mock_udp_test_' . \uniqid();
        \mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        $files = \glob($this->tempDir . '/*');

        if ($files) {
            foreach ($files as $file) {
                \unlink($file);
            }
        }
        \rmdir($this->tempDir);
    }

    public function testLoadFixtureWithNonexistentFile(): void
    {
        $mockClient = new MockUdpClient;
        $result     = $mockClient->loadFixture('/nonexistent/file.json');
        $this->assertFalse($result);
    }

    public function testLoadFixtureWithValidFixture(): void
    {
        $fixtureData = [
            'captures' => [
                [
                    'sent'      => \base64_encode('request1'),
                    'received'  => \base64_encode('response1'),
                    'timestamp' => 1234567890.123,
                ],
                [
                    'sent'      => \base64_encode('request2'),
                    'received'  => \base64_encode('response2'),
                    'timestamp' => 1234567891.456,
                ],
            ],
        ];

        $fixtureFile = $this->tempDir . '/valid_fixture.json';
        \file_put_contents($fixtureFile, \json_encode($fixtureData));

        $mockClient = new MockUdpClient;
        $result     = $mockClient->loadFixture($fixtureFile);
        $this->assertTrue($result);
        $this->assertEquals(2, $mockClient->getCaptureCount());
    }

    public function testLoadFixtureWithInvalidJson(): void
    {
        $fixtureFile = $this->tempDir . '/invalid_json.json';
        \file_put_contents($fixtureFile, 'invalid json content');

        $mockClient = new MockUdpClient;
        $result     = $mockClient->loadFixture($fixtureFile);
        $this->assertFalse($result);
    }

    public function testLoadFixtureWithMissingCaptures(): void
    {
        $fixtureData = [
            'metadata' => 'some data',
        ];

        $fixtureFile = $this->tempDir . '/no_captures.json';
        \file_put_contents($fixtureFile, \json_encode($fixtureData));

        $mockClient = new MockUdpClient;
        $result     = $mockClient->loadFixture($fixtureFile);
        $this->assertFalse($result);
    }

    public function testLoadFixtureWithEmptyCaptures(): void
    {
        $fixtureData = [
            'captures' => [],
        ];

        $fixtureFile = $this->tempDir . '/empty_captures.json';
        \file_put_contents($fixtureFile, \json_encode($fixtureData));

        $mockClient = new MockUdpClient;
        $result     = $mockClient->loadFixture($fixtureFile);
        $this->assertFalse($result);
    }

    public function testQueryWithMatchingRequest(): void
    {
        $fixtureData = [
            'captures' => [
                [
                    'sent'      => \base64_encode('test_request'),
                    'received'  => \base64_encode('test_response'),
                    'timestamp' => 1234567890.123,
                ],
            ],
        ];

        $fixtureFile = $this->tempDir . '/matching_request.json';
        \file_put_contents($fixtureFile, \json_encode($fixtureData));

        $mockClient = new MockUdpClient;
        $mockClient->loadFixture($fixtureFile);

        $response = $mockClient->query('127.0.0.1', 12345, 'test_request');
        $this->assertEquals('test_response', $response);
    }

    public function testQueryWithNonMatchingRequest(): void
    {
        $fixtureData = [
            'captures' => [
                [
                    'sent'      => \base64_encode('expected_request'),
                    'received'  => \base64_encode('response'),
                    'timestamp' => 1234567890.123,
                ],
            ],
        ];

        $fixtureFile = $this->tempDir . '/non_matching_request.json';
        \file_put_contents($fixtureFile, \json_encode($fixtureData));

        $mockClient = new MockUdpClient;
        $mockClient->loadFixture($fixtureFile);

        // Should fall back to sequential response
        $response = $mockClient->query('127.0.0.1', 12345, 'different_request');
        $this->assertEquals('response', $response);
    }

    public function testQueryWithEmptySentField(): void
    {
        $fixtureData = [
            'captures' => [
                [
                    'sent'      => '',
                    'received'  => \base64_encode('response_for_empty'),
                    'timestamp' => 1234567890.123,
                ],
            ],
        ];

        $fixtureFile = $this->tempDir . '/empty_sent.json';
        \file_put_contents($fixtureFile, \json_encode($fixtureData));

        $mockClient = new MockUdpClient;
        $mockClient->loadFixture($fixtureFile);

        $response = $mockClient->query('127.0.0.1', 12345, 'any_request');
        $this->assertEquals('response_for_empty', $response);
    }

    public function testQuerySequentialResponses(): void
    {
        $fixtureData = [
            'captures' => [
                [
                    'received'  => \base64_encode('response1'),
                    'timestamp' => 1234567890.123,
                ],
                [
                    'received'  => \base64_encode('response2'),
                    'timestamp' => 1234567891.456,
                ],
                [
                    'received'  => \base64_encode('response3'),
                    'timestamp' => 1234567892.789,
                ],
            ],
        ];

        $fixtureFile = $this->tempDir . '/sequential.json';
        \file_put_contents($fixtureFile, \json_encode($fixtureData));

        $mockClient = new MockUdpClient;
        $mockClient->loadFixture($fixtureFile);

        $this->assertEquals('response1', $mockClient->query('127.0.0.1', 12345, 'req1'));
        $this->assertEquals('response2', $mockClient->query('127.0.0.1', 12345, 'req2'));
        $this->assertEquals('response3', $mockClient->query('127.0.0.1', 12345, 'req3'));
        $this->assertNull($mockClient->query('127.0.0.1', 12345, 'req4')); // No more responses
    }

    public function testQueryWithNoCaptures(): void
    {
        $mockClient = new MockUdpClient;
        $response   = $mockClient->query('127.0.0.1', 12345, 'test');
        $this->assertNull($response);
    }

    public function testReset(): void
    {
        $fixtureData = [
            'captures' => [
                [
                    'received'  => \base64_encode('response1'),
                    'timestamp' => 1234567890.123,
                ],
                [
                    'received'  => \base64_encode('response2'),
                    'timestamp' => 1234567891.456,
                ],
            ],
        ];

        $fixtureFile = $this->tempDir . '/reset_test.json';
        \file_put_contents($fixtureFile, \json_encode($fixtureData));

        $mockClient = new MockUdpClient;
        $mockClient->loadFixture($fixtureFile);

        // Use first response
        $this->assertEquals('response1', $mockClient->query('127.0.0.1', 12345, 'req1'));
        // Use second response
        $this->assertEquals('response2', $mockClient->query('127.0.0.1', 12345, 'req2'));
        // No more responses
        $this->assertNull($mockClient->query('127.0.0.1', 12345, 'req3'));

        // Reset and start over
        $mockClient->reset();
        $this->assertEquals('response1', $mockClient->query('127.0.0.1', 12345, 'req1'));
        $this->assertEquals('response2', $mockClient->query('127.0.0.1', 12345, 'req2'));
    }

    public function testGetCaptureCount(): void
    {
        $mockClient = new MockUdpClient;
        $this->assertEquals(0, $mockClient->getCaptureCount());

        $fixtureData = [
            'captures' => [
                ['received' => \base64_encode('resp1')],
                ['received' => \base64_encode('resp2')],
                ['received' => \base64_encode('resp3')],
            ],
        ];

        $fixtureFile = $this->tempDir . '/count_test.json';
        \file_put_contents($fixtureFile, \json_encode($fixtureData));

        $mockClient->loadFixture($fixtureFile);
        $this->assertEquals(3, $mockClient->getCaptureCount());
    }

    public function testLoadFixtureWithMissingFields(): void
    {
        $fixtureData = [
            'captures' => [
                [
                    // Missing 'sent', 'received', and 'timestamp' fields
                ],
                [
                    'sent' => \base64_encode('request'),
                    // Missing 'received'
                ],
            ],
        ];

        $fixtureFile = $this->tempDir . '/missing_fields.json';
        \file_put_contents($fixtureFile, \json_encode($fixtureData));

        $mockClient = new MockUdpClient;
        $result     = $mockClient->loadFixture($fixtureFile);
        $this->assertTrue($result); // Should still load, just with null values
        $this->assertEquals(2, $mockClient->getCaptureCount());
    }

    public function testLoadFixtureWithInvalidBase64(): void
    {
        $fixtureData = [
            'captures' => [
                [
                    'sent'      => 'invalid base64!!!',
                    'received'  => \base64_encode('valid_response'),
                    'timestamp' => 1234567890.123,
                ],
            ],
        ];

        $fixtureFile = $this->tempDir . '/invalid_base64.json';
        \file_put_contents($fixtureFile, \json_encode($fixtureData));

        $mockClient = new MockUdpClient;
        $result     = $mockClient->loadFixture($fixtureFile);
        $this->assertTrue($result); // Should load but with false decoded sent data
        $this->assertEquals(1, $mockClient->getCaptureCount());
    }

    public function testQueryAfterResetWithMatchingRequests(): void
    {
        $fixtureData = [
            'captures' => [
                [
                    'sent'      => \base64_encode('request1'),
                    'received'  => \base64_encode('response1'),
                    'timestamp' => 1234567890.123,
                ],
                [
                    'sent'      => \base64_encode('request2'),
                    'received'  => \base64_encode('response2'),
                    'timestamp' => 1234567891.456,
                ],
            ],
        ];

        $fixtureFile = $this->tempDir . '/reset_matching.json';
        \file_put_contents($fixtureFile, \json_encode($fixtureData));

        $mockClient = new MockUdpClient;
        $mockClient->loadFixture($fixtureFile);

        // First round
        $this->assertEquals('response1', $mockClient->query('127.0.0.1', 12345, 'request1'));
        $this->assertEquals('response2', $mockClient->query('127.0.0.1', 12345, 'request2'));

        // Reset and try again
        $mockClient->reset();
        $this->assertEquals('response1', $mockClient->query('127.0.0.1', 12345, 'request1'));
        $this->assertEquals('response2', $mockClient->query('127.0.0.1', 12345, 'request2'));
    }
}
