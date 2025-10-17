# Capture System Documentation

## Overview

The Clansuite Server Query library includes a capture system designed to create reliable test fixtures for game server protocol testing. This system captures server information from live game servers and stores it as JSON test fixtures, enabling consistent testing without external dependencies.

The capture system is built around a modular, object-oriented architecture that supports multiple capture strategies and provides comprehensive server information extraction and storage capabilities.

## Purpose

This system allows developers to:
- Capture real server information from game servers
- Store structured data as JSON fixtures for testing
- Create reproducible test scenarios
- Debug protocol implementations
- Enable offline testing and CI runs
- Support multiple capture strategies (direct vs. worker-based)

## Architecture

### Core Components

#### 1. **CaptureService** (`src/Capture/CaptureService.php`)
- Main orchestration service for the capture system
- Coordinates protocol resolution, capture execution, and storage
- Provides unified interface for different capture strategies
- Handles version normalization and metadata extraction

#### 2. **ProtocolResolver** (`src/Capture/Protocol/ProtocolResolver.php`)
- Resolves protocol names to ProtocolInterface implementations
- Uses ServerProtocols registry for mapping
- Supports auto-detection for unknown protocols

#### 3. **ProtocolInterface** (`src/Capture/Protocol/ProtocolInterface.php`)
- Defines the contract for protocol implementations
- Provides query() method for server information retrieval
- Includes version extraction and protocol naming

#### 4. **Capture Strategies**
- **DirectCaptureStrategy** (`src/Capture/Strategy/DirectCaptureStrategy.php`): Performs queries directly in the current process
- **WorkerCaptureStrategy** (`src/Capture/Strategy/WorkerCaptureStrategy.php`): Uses isolated worker for enhanced reliability and timeout control

#### 5. **CaptureWorker** (`src/Capture/Worker/CaptureWorker.php`)
- Handles actual server querying with timeout and retry logic
- Collects debug information during queries
- Provides structured server information extraction

#### 6. **Data Processing**
- **ServerInfoExtractor** (`src/Capture/Extractor/ServerInfoExtractor.php`): Extracts server information from raw responses
- **VersionNormalizer** (`src/Capture/Extractor/VersionNormalizer.php`): Normalizes version strings for consistent storage

#### 7. **Storage Layer**
- **JsonFixtureStorage** (`src/Capture/Storage/JsonFixtureStorage.php`): Stores capture results as JSON fixtures
- **FixtureStorageInterface**: Defines storage contract for extensibility

#### 8. **Data Models**
- **CaptureResult**: Contains raw packets, server info, and metadata
- **ServerInfo**: Structured server information (address, players, rules, etc.)
- **ServerAddress**: IP and port combination

## Capture Process

### Workflow Steps

1. **Initialization**
   - Load configuration from `config/capture_config.php`
   - Create CaptureService with appropriate strategy
   - Resolve protocol using ProtocolResolver

2. **Server Query Execution**
   - Execute capture using selected strategy (Direct or Worker)
   - Perform network queries to game server
   - Collect debug information and raw responses

3. **Data Processing**
   - Extract server information using ServerInfoExtractor
   - Normalize version information
   - Structure data according to CaptureResult format

4. **Fixture Storage**
   - Store structured captures as JSON files
   - Organize by protocol and normalized version
   - Enable replay-based testing

## Configuration

The capture system is configured via `config/capture_config.php`:

```php
<?php return [
    'fixtures_dir' => __DIR__ . '/../tests/fixtures',  // Storage directory
    'default_timeout' => 10,                           // Default query timeout
    'worker_timeout' => 10,                            // Worker-specific timeout
    'use_worker' => false,                             // Use WorkerCaptureStrategy
];
```

## File Structure & Format

Captures are organized as:
```
tests/fixtures/
├── {protocol}/
│   └── {normalized_version}/
│       └── capture_{ip}_{port}.json
```

### JSON File Structure

```json
{
  "metadata": {
    "ip": "192.168.1.1",
    "port": 27015,
    "protocol": "source",
    "timestamp": 1759296561,
    "worker_used": false
  },
  "packets": ["base64_encoded_packet_data"],
  "server_info": {
    "address": "192.168.1.1",
    "queryport": 27015,
    "online": true,
    "gamename": "Counter-Strike",
    "gameversion": "1.1.2.7",
    "servertitle": "Test Server",
    "mapname": "de_dust2",
    "gametype": "0",
    "numplayers": 5,
    "maxplayers": 32,
    "rules": {
      "sv_password": "0",
      "mp_timelimit": "30"
    },
    "players": [
      {
        "name": "Player1",
        "score": 150,
        "time": 125.5
      }
    ]
  }
}
```

## CLI Usage

The capture system is integrated into the main `bin/capture.php` tool:

```bash
php bin/capture.php <ip> <port> [protocol] [options]
```

**Examples:**
```bash
php bin/capture.php 192.168.1.1 27015 source
php bin/capture.php 176.31.226.111 27015 arkse
php bin/capture.php list  # List available fixtures
```

### Configuration Options

- `use_worker`: Enable worker-based capture strategy
- `worker_timeout`: Timeout for worker-based captures
- `default_timeout`: Default timeout for direct captures

## Capture Strategies

### Direct Capture Strategy

- **Pros**: Simple, low overhead and direct execution.
- **Cons**: Has potential to hanging on slow or unresponsive servers.
- **Use Case**: Protocol development and testing using reliable servers.

### Worker Capture Strategy

- **Pros**: Process isolation, reliable timeouts, enhanced error handling.
- **Cons**: This has a slightly higher overhead.
- **Use Case**: Production captures, CI/CD pipelines, unreliable networks.

## Protocol Support

The system supports all protocols defined in `ServerProtocols::getProtocolsMap()`, including:

- Steam-based protocols (Source, GoldSource)
- Quake series (Quake, Quake2, Quake3, Quake4)
- Battle.net protocols
- Custom game protocols

## API Reference

### CaptureService

```php
class CaptureService
{
    public function capture(
        string $ip,
        int $port,
        string $protocol = 'auto',
        array $options = []
    ): string
}
```

Main capture method that orchestrates the entire capture process.

### CaptureStrategyInterface

```php
interface CaptureStrategyInterface
{
    public function capture(
        ProtocolInterface $protocol,
        ServerAddress $addr,
        array $options
    ): CaptureResult;
}
```

Contract for capture strategy implementations.

### CaptureWorker

```php
class CaptureWorker
{
    public function __construct(int $timeout = 5, int $maxRetries = 2)
    public function query(string $protocol, string $ip, int $port): array
}
```

Handles server querying with timeout and retry logic.

## Testing and Development

### Unit Testing

- Test individual components in isolation
- Mock ProtocolInterface for strategy testing
- Use fixtures for deterministic testing

### Integration Testing

- Test full capture workflows
- Validate fixture generation
- Verify protocol compatibility

### Replay Testing

The capture system enables replay-based testing where stored fixtures
can be used to test protocol parsing without live servers.

## Troubleshooting

### Common Issues

1. **Protocol Resolution Errors**
   - Verify protocol name is in ServerProtocols map
   - Check for typos in protocol names

2. **Network Timeouts**
   - Increase timeout values in configuration
   - Use worker strategy for better timeout handling
   - Check network connectivity

3. **Storage Permission Errors**
   - Ensure write access to fixtures directory
   - Check file system permissions

4. **Empty Capture Results**
   - Server may be offline or unresponsive
   - Protocol mismatch
   - Firewall blocking queries
