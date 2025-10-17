# Clansuite Server Query

Clansuite Server Query is a PHP library that allows you to query
game and voice servers. See [list of supported servers](SUPPORTED_SERVERS.md).

It is a full rewrite of the deprecated gsQuery by Jeremias Reith.
Also inspired by projects like hlsw, kquery, squery, GameQ and phgstats.

## Requirements

- PHP 8.2+

## Installation

`composer require clansuite/gameserverquery`

## Usage

### Basic Usage

```php
require_once 'vendor/autoload.php';

$factory = new Clansuite\ServerQuery\CSQuery();
$server = $factory->createInstance('Quake3a', '172.104.253.108', 27980);

if ($server->query_server()) {
    // Get JSON output
    $json = $server->toJson();

    // Get HTML table output
    $html = $server->toHtml();

    echo $html; // Display in browser
}
```

### Web Interface

Use the included web interface:

```bash
# JSON output (default)
curl "http://localhost/serializer.php?protocol=Quake3a&host=172.104.253.108&queryport=27980"

# HTML output
curl "http://localhost/serializer.php?protocol=Quake3a&host=172.104.253.108&queryport=27980&format=html"
```

The HTML output provides a styled table view of server information, players, and server rules.

### Capture Tool

To capture network packets for testing fixtures:

```bash
php bin/capture 192.168.1.10 27015 source
```

This saves fixtures in `/tests/fixtures/` for reliable testing without external dependencies.

Configuration is in `/config/capture_config.php`.

## Testing

Testing a server query library requires validating its ability to communicate
with real game or voice servers and correctly parse their responses.

To ensure reliability and compatibility across different server implementations,
it’s helpful to cross-reference results with established server monitoring
and listing platforms, such as:

- https://www.gametracker.com/
- https://listforge.net/
- https://gamemonitoring.net/
- https://www.battlemetrics.com/servers/ark

These services provide up-to-date server status, player counts, and metadata,
making them valuable benchmarks for verifying the accuracy and robustness
of a server query protocol implementation.

## Documentation

- [User Manual](docs/user-manual/) - Installation, usage, and troubleshooting.
- [Developer Manual](docs/developer-manual/) - Architecture, API, and contribution guide.
- [API Documentation](https://api.clansuite.com) - Generated API documentation.
