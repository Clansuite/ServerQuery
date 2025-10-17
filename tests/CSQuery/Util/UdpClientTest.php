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

use Clansuite\ServerQuery\Util\UdpClient;
use PHPUnit\Framework\TestCase;

final class UdpClientTest extends TestCase
{
    public function testSetTimeout(): void
    {
        $client = new UdpClient;
        $client->setTimeout(30);

        // Test that setTimeout doesn't throw an exception
        $this->assertInstanceOf(UdpClient::class, $client);
    }

    public function testParsePlayerResponseEmptyData(): void
    {
        $client = new UdpClient;

        $reflection = new ReflectionClass($client);
        $method     = $reflection->getMethod('parsePlayerResponse');
        $method->setAccessible(true);

        // Test with minimal valid data (header + 0 players)
        $data   = "\xFF\xFF\xFF\xFF\x55\x00";
        $result = $method->invoke($client, $data);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testParsePlayerResponseSinglePlayer(): void
    {
        $client = new UdpClient;

        $reflection = new ReflectionClass($client);
        $method     = $reflection->getMethod('parsePlayerResponse');
        $method->setAccessible(true);

        // Create test data for one player
        $data = "\xFF\xFF\xFF\xFF\x55"; // header
        $data .= "\x01"; // 1 player
        $data .= "\x00"; // player index
        $data .= "TestPlayer\x00"; // player name + null terminator
        $data .= \pack('l', 100); // score (little-endian int32)
        $data .= \pack('f', 123.45); // time (little-endian float32)

        $result = $method->invoke($client, $data);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(0, $result[0]['index']);
        $this->assertEquals('TestPlayer', $result[0]['name']);
        $this->assertEquals(100, $result[0]['score']);
        $this->assertEqualsWithDelta(123.45, $result[0]['time'], 0.01);
    }

    public function testParsePlayerResponseMultiplePlayers(): void
    {
        $client = new UdpClient;

        $reflection = new ReflectionClass($client);
        $method     = $reflection->getMethod('parsePlayerResponse');
        $method->setAccessible(true);

        // Create test data for two players
        $data = "\xFF\xFF\xFF\xFF\x55"; // header
        $data .= "\x02"; // 2 players

        // Player 1
        $data .= "\x01"; // player index
        $data .= "Player1\x00"; // player name + null terminator
        $data .= \pack('l', 50); // score
        $data .= \pack('f', 67.89); // time

        // Player 2
        $data .= "\x02"; // player index
        $data .= "Player2\x00"; // player name + null terminator
        $data .= \pack('l', 75); // score
        $data .= \pack('f', 98.76); // time

        $result = $method->invoke($client, $data);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // Check player 1
        $this->assertEquals(1, $result[0]['index']);
        $this->assertEquals('Player1', $result[0]['name']);
        $this->assertEquals(50, $result[0]['score']);
        $this->assertEqualsWithDelta(67.89, $result[0]['time'], 0.01);

        // Check player 2
        $this->assertEquals(2, $result[1]['index']);
        $this->assertEquals('Player2', $result[1]['name']);
        $this->assertEquals(75, $result[1]['score']);
        $this->assertEqualsWithDelta(98.76, $result[1]['time'], 0.01);
    }

    public function testParsePlayerResponseTruncatedData(): void
    {
        $client = new UdpClient;

        $reflection = new ReflectionClass($client);
        $method     = $reflection->getMethod('parsePlayerResponse');
        $method->setAccessible(true);

        // Test with truncated data (claims 2 players but only enough data for 1)
        $data = "\xFF\xFF\xFF\xFF\x55"; // header
        $data .= "\x02"; // 2 players (claims)
        $data .= "\x00"; // player index
        $data .= "Test\x00"; // player name + null terminator
        $data .= \pack('l', 100); // score
        $data .= \pack('f', 123.45); // time
        // Missing data for second player

        $result = $method->invoke($client, $data);

        $this->assertIsArray($result);
        // Should only parse complete players (1 player)
        $this->assertCount(1, $result);
    }

    public function testParsePlayerResponseEmptyName(): void
    {
        $client = new UdpClient;

        $reflection = new ReflectionClass($client);
        $method     = $reflection->getMethod('parsePlayerResponse');
        $method->setAccessible(true);

        // Player with empty name
        $data = "\xFF\xFF\xFF\xFF\x55"; // header
        $data .= "\x01"; // 1 player
        $data .= "\x05"; // player index
        $data .= "\x00"; // empty name (just null terminator)
        $data .= \pack('l', 25); // score
        $data .= \pack('f', 12.34); // time

        $result = $method->invoke($client, $data);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(5, $result[0]['index']);
        $this->assertEquals('', $result[0]['name']);
        $this->assertEquals(25, $result[0]['score']);
        $this->assertEqualsWithDelta(12.34, $result[0]['time'], 0.01);
    }

    public function testParsePlayerResponseZeroPlayers(): void
    {
        $client = new UdpClient;

        $reflection = new ReflectionClass($client);
        $method     = $reflection->getMethod('parsePlayerResponse');
        $method->setAccessible(true);

        // Zero players
        $data = "\xFF\xFF\xFF\xFF\x55\x00";

        $result = $method->invoke($client, $data);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testQueryPlayersWithMockClient(): void
    {
        // Create a partial mock of UdpClient to mock the query method
        $client = $this->getMockBuilder(UdpClient::class)
            ->onlyMethods(['query'])
            ->getMock();

        // Mock the challenge response
        $challengeResponse = "\xFF\xFF\xFF\xFF\x41\x01\x02\x03\x04"; // A2S_PLAYER response with challenge
        $playerResponse    = "\xFF\xFF\xFF\xFF\x44\x00"; // Minimal player response

        $client->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls($challengeResponse, $playerResponse);

        // This should work now with both query calls mocked
        $result = $client->queryPlayers('127.0.0.1', 27015);
        $this->assertIsArray($result);
        $this->assertEmpty($result); // Empty because player response has 0 players
    }

    public function testQueryWithMockStream(): void
    {
        // Register a mock UDP stream wrapper
        if (!\in_array('mockudp', \stream_get_wrappers(), true)) {
            \stream_wrapper_register('mockudp', MockUdpStreamWrapper::class);
        }

        // Set up the mock response
        MockUdpStreamWrapper::setResponse('test response data');

        // Create a partial mock to override fsockopen behavior
        $client = $this->getMockBuilder(UdpClient::class)
            ->onlyMethods(['createSocket'])
            ->getMock();

        $client->method('createSocket')
            ->with('127.0.0.1', 27015)
            ->willReturn(\fopen('mockudp://test', 'r+'));

        // Test successful query
        $reflection  = new ReflectionClass($client);
        $queryMethod = $reflection->getMethod('query');
        $queryMethod->setAccessible(true);

        $result = $queryMethod->invoke($client, '127.0.0.1', 27015, 'test packet');
        $this->assertEquals('test response data', $result);

        // Clean up
        if (\in_array('mockudp', \stream_get_wrappers(), true)) {
            \stream_wrapper_unregister('mockudp');
        }
    }

    public function testQueryWithConnectionFailure(): void
    {
        // Test with a mock that simulates connection failure
        $client = $this->getMockBuilder(UdpClient::class)
            ->onlyMethods(['createSocket'])
            ->getMock();

        $client->method('createSocket')
            ->willReturn(false);

        $reflection  = new ReflectionClass($client);
        $queryMethod = $reflection->getMethod('query');
        $queryMethod->setAccessible(true);

        $result = $queryMethod->invoke($client, '127.0.0.1', 27015, 'test packet');
        $this->assertNull($result);
    }

    public function testQueryWithWriteFailure(): void
    {
        // Register mock stream that fails on write
        if (!\in_array('mockudp', \stream_get_wrappers(), true)) {
            \stream_wrapper_register('mockudp', MockUdpStreamWrapper::class);
        }

        MockUdpStreamWrapper::setWriteFailure(true);

        $client = $this->getMockBuilder(UdpClient::class)
            ->onlyMethods(['createSocket'])
            ->getMock();

        $client->method('createSocket')
            ->with('127.0.0.1', 27015)
            ->willReturn(\fopen('mockudp://test', 'r+'));

        $reflection  = new ReflectionClass($client);
        $queryMethod = $reflection->getMethod('query');
        $queryMethod->setAccessible(true);

        $result = $queryMethod->invoke($client, '127.0.0.1', 27015, 'test packet');
        $this->assertNull($result);

        // Clean up
        if (\in_array('mockudp', \stream_get_wrappers(), true)) {
            \stream_wrapper_unregister('mockudp');
        }
    }

    public function testQueryWithEmptyResponse(): void
    {
        // Register mock stream that returns empty response
        if (!\in_array('mockudp', \stream_get_wrappers(), true)) {
            \stream_wrapper_register('mockudp', MockUdpStreamWrapper::class);
        }

        MockUdpStreamWrapper::setResponse('');

        $client = $this->getMockBuilder(UdpClient::class)
            ->onlyMethods(['createSocket'])
            ->getMock();

        $client->method('createSocket')
            ->with('127.0.0.1', 27015)
            ->willReturn(\fopen('mockudp://test', 'r+'));

        $reflection  = new ReflectionClass($client);
        $queryMethod = $reflection->getMethod('query');
        $queryMethod->setAccessible(true);

        $result = $queryMethod->invoke($client, '127.0.0.1', 27015, 'test packet');
        $this->assertNull($result);

        // Clean up
        if (\in_array('mockudp', \stream_get_wrappers(), true)) {
            \stream_wrapper_unregister('mockudp');
        }
    }

    public function testQueryWithZeroResponse(): void
    {
        // Register mock stream that returns '0'
        if (!\in_array('mockudp', \stream_get_wrappers(), true)) {
            \stream_wrapper_register('mockudp', MockUdpStreamWrapper::class);
        }

        MockUdpStreamWrapper::setResponse('0');

        $client = $this->getMockBuilder(UdpClient::class)
            ->onlyMethods(['createSocket'])
            ->getMock();

        $client->method('createSocket')
            ->with('127.0.0.1', 27015)
            ->willReturn(\fopen('mockudp://test', 'r+'));

        $reflection  = new ReflectionClass($client);
        $queryMethod = $reflection->getMethod('query');
        $queryMethod->setAccessible(true);

        $result = $queryMethod->invoke($client, '127.0.0.1', 27015, 'test packet');
        $this->assertNull($result);

        // Clean up
        if (\in_array('mockudp', \stream_get_wrappers(), true)) {
            \stream_wrapper_unregister('mockudp');
        }
    }

    public function testQueryWithTimeout(): void
    {
        // Register mock stream that simulates timeout
        if (!\in_array('mockudp', \stream_get_wrappers(), true)) {
            \stream_wrapper_register('mockudp', MockUdpStreamWrapper::class);
        }

        MockUdpStreamWrapper::setTimeout(true);

        $client = $this->getMockBuilder(UdpClient::class)
            ->onlyMethods(['createSocket'])
            ->getMock();

        $client->method('createSocket')
            ->with('127.0.0.1', 27015)
            ->willReturn(\fopen('mockudp://test', 'r+'));

        $reflection  = new ReflectionClass($client);
        $queryMethod = $reflection->getMethod('query');
        $queryMethod->setAccessible(true);

        $result = $queryMethod->invoke($client, '127.0.0.1', 27015, 'test packet');
        $this->assertNull($result);

        // Clean up
        if (\in_array('mockudp', \stream_get_wrappers(), true)) {
            \stream_wrapper_unregister('mockudp');
        }
    }

    public function testQueryPlayersWithInvalidChallengeResponse(): void
    {
        $client = $this->getMockBuilder(UdpClient::class)
            ->onlyMethods(['query'])
            ->getMock();

        // Mock invalid challenge response (too short)
        $challengeResponse = "\xFF\xFF\xFF\xFF\x41\x01"; // Too short
        $client->expects($this->once())
            ->method('query')
            ->willReturn($challengeResponse);

        $result = $client->queryPlayers('127.0.0.1', 27015);
        $this->assertNull($result);
    }

    public function testQueryPlayersWithNullChallengeResponse(): void
    {
        $client = $this->getMockBuilder(UdpClient::class)
            ->onlyMethods(['query'])
            ->getMock();

        // Mock null challenge response
        $client->expects($this->once())
            ->method('query')
            ->willReturn(null);

        $result = $client->queryPlayers('127.0.0.1', 27015);
        $this->assertNull($result);
    }

    public function testQueryPlayersWithInvalidPlayerResponse(): void
    {
        $client = $this->getMockBuilder(UdpClient::class)
            ->onlyMethods(['query'])
            ->getMock();

        // Mock valid challenge response
        $challengeResponse = "\xFF\xFF\xFF\xFF\x41\x01\x02\x03\x04";
        // Mock invalid player response (too short)
        $playerResponse = "\xFF\xFF\xFF\xFF\x44";

        $client->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls($challengeResponse, $playerResponse);

        $result = $client->queryPlayers('127.0.0.1', 27015);
        $this->assertNull($result);
    }

    public function testQueryPlayersWithNullPlayerResponse(): void
    {
        $client = $this->getMockBuilder(UdpClient::class)
            ->onlyMethods(['query'])
            ->getMock();

        // Mock valid challenge response
        $challengeResponse = "\xFF\xFF\xFF\xFF\x41\x01\x02\x03\x04";
        // Mock null player response
        $playerResponse = null;

        $client->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls($challengeResponse, $playerResponse);

        $result = $client->queryPlayers('127.0.0.1', 27015);
        $this->assertNull($result);
    }
}

/**
 * Mock UDP Stream Wrapper for testing network operations.
 */
class MockUdpStreamWrapper
{
    private static string $response   = '';
    private static bool $writeFailure = false;
    private static bool $timeout      = false;
    public mixed $context;
    private bool $eof     = false;
    private int $position = 0;

    public static function setResponse(string $response): void
    {
        self::$response     = $response;
        self::$writeFailure = false;
        self::$timeout      = false;
    }

    public static function setWriteFailure(bool $fail): void
    {
        self::$writeFailure = $fail;
    }

    public static function setTimeout(bool $timeout): void
    {
        self::$timeout = $timeout;
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        return true;
    }

    public function stream_write(string $data): false|int
    {
        if (self::$writeFailure) {
            return false;
        }

        return \strlen($data);
    }

    public function stream_read(int $count): string
    {
        if (self::$timeout) {
            $this->eof = true;

            return '';
        }

        if ($this->position >= \strlen(self::$response)) {
            $this->eof = true;

            return '';
        }

        $remaining  = \strlen(self::$response) - $this->position;
        $readLength = \min($count, $remaining);
        $result     = \substr(self::$response, $this->position, $readLength);
        $this->position += $readLength;

        return $result;
    }

    public function stream_eof(): bool
    {
        return $this->eof;
    }

    public function stream_stat(): array
    {
        return [];
    }

    public function stream_close(): void
    {
        // Reset state
        $this->eof      = false;
        $this->position = 0;
    }

    public function stream_set_option(int $option, int $arg1, ?int $arg2): bool
    {
        // Mock implementation for stream_set_blocking and stream_set_timeout
        return true;
    }

    public function stream_metadata(string $path, int $option, mixed $value): bool
    {
        return true;
    }
}
