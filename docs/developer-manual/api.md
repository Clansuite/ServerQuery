# API Documentation

## CSQuery Factory

### Class: `Clansuite\ServerQuery\CSQuery`

Central factory for creating server instances.

#### Constructor

```php
public function __construct(string $address = '127.0.0.1', int $queryport = 0)
```

- `$address`: Placeholder IP (default '127.0.0.1')
- `$queryport`: Placeholder port (default 0)

#### Methods

- `createInstance(string $protocol, string $address, int $port): ServerProtocol`
  - Creates a server instance for the given protocol.
  - Returns: Protocol-specific server object.

## Server Protocols

All protocols extend a base class and implement querying.

### Common Methods

- `query_server(bool $getPlayers = true, bool $getRules = true): bool`
  - Queries the server.
  - Returns: True on success.

- `toJson(): string`
  - Returns server data as JSON.

- `toHtml(): string`
  - Returns server data as HTML table.

### Properties

- `address`, `queryport`: Server details.
- `online`: Boolean status.
- `numplayers`, `maxplayers`: Player counts.
- `mapname`, `gamename`: Game details.
- `players`: Array of player data.
- `rules`: Array of server rules.

## Capture API

### Class: `Clansuite\Capture\CaptureService`

Handles capture operations.

#### Constructor

```php
public function __construct(
    ProtocolResolver $resolver,
    CaptureStrategyInterface $strategy,
    FixtureStorageInterface $storage,
    ServerInfoExtractor $extractor,
    VersionNormalizer $normalizer
)
```

#### Methods

- `capture(string $ip, int $port, string $protocol = 'auto', array $options = []): void`
  - Captures server response and saves fixture.

### Interfaces

- `ProtocolInterface`: Defines `query()`, `getProtocolName()`, `getVersion()`.
- `CaptureStrategyInterface`: Defines `capture()`.
- `FixtureStorageInterface`: Defines `save()`, `load()`, `listAll()`.

## CLI Commands

- `CaptureCommand`: Handles `bin/capture` execution.
- `ListCapturesCommand`: Lists fixtures.

## Examples

### Basic Query

```php
$factory = new CSQuery();
$server = $factory->createInstance('Quake3a', '192.168.1.1', 27960);
$server->query_server();
echo $server->toJson();
```

### Capture

```php
use function Clansuite\Capture\createCaptureService;

$service = createCaptureService(); // From bootstrap
$service->capture('192.168.1.1', 27015, 'source');
```
