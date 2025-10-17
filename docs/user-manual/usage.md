# Usage

## Basic Querying

To query a game server, create a factory instance and use it to create
a server object for the desired protocol.

### Example: Querying a Quake 3 Arena Server

```php
require_once 'vendor/autoload.php';

use Clansuite\ServerQuery\CSQuery;

// Create a server instance
$server = (new CSQuery())->createInstance('Quake3a', '172.104.253.108', 27980);

// Query the server
if ($server->query_server()) {
    echo "Server is online!\n";
    echo "Players: " . $server->numplayers . "/" . $server->maxplayers . "\n";
    echo "Map: " . $server->mapname . "\n";
} else {
    echo "Server is offline or unreachable.\n";
}
```

### Constructor:

#### Understanding GamePort vs. QueryPort

- **GamePort**: The port your game client uses to connect to the server for actual gameplay.
- **QueryPort**: A separate port used by monitoring or data clients to request live server information (e.g., player count, map, server status).

#### Constructor Parameters

The constructor accepts two parameters:
1. **IP address** (or an `IP:GamePort` string)
2. **QueryPort** (optional when using the `IP:GamePort` format)

#### Automatic QueryPort Detection

If you don’t know the QueryPort, simply provide the server address in the
standard `IP:GamePort` format as the first argument:

```
$server = (new CSQuery())->createInstance('ArmaReforger', '159.69.139.30:2001');
```

CSQuery will then automatically determine the correct QueryPort based on the game type.

For Arma Reforger, a GamePort of 2001 corresponds to QueryPort 17777,
which the library uses internally to fetch live server data.

### Supported Protocols

Use the internal ID from the [supported servers list](SUPPORTED_SERVERS.md). Common examples:

- `Quake3a` for Quake 3 Arena
- `halflife` for Half-Life
- `bf4` for Battlefield 4

## Output Formats

### JSON Output

```php
$json = $server->toJson();
echo $json;
```

### HTML Table

```php
$html = $server->toHtml();
echo $html; // Displays a styled table
```

## Web Interface

Use the included `serializer.php` script for web-based queries.

### JSON Output (Default)

```
http://localhost/serializer.php?protocol=Quake3a&host=172.104.253.108&queryport=27980
```

### HTML Output

```
http://localhost/serializer.php?protocol=Quake3a&host=172.104.253.108&queryport=27980&format=html
```

## Capturing Fixtures

For testing and development, capture server responses:

```bash
php bin/capture 192.168.1.10 27015 source
```

This saves fixtures in `tests/fixtures/` for offline replay.

## Advanced Usage

### Querying with Players and Rules

```php
$server->query_server(true, true); // Include players and rules
```

### Handling Errors

```php
if (!$server->query_server()) {
    echo "Error: " . $server->errstr . "\n";
}
```

## Examples

See the `examples/` directory for more sample scripts.
