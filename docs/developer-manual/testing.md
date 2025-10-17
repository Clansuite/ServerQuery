# Testing

## Overview

The project uses PHPUnit for unit and integration testing. Fixtures enable testing without live servers.

## Running Tests

- **All Tests**: `composer tests-fast`
- **Specific Test**: `vendor/bin/phpunit tests/CSQuery/CSQueryTest.php`
- **With Coverage**: `vendor/bin/phpunit --coverage-html coverage/`

## Test Structure

- `tests/CSQuery/`: Unit tests for core components.
- `tests/unit/Capture/`: Tests for capture system.
- `tests/fixtures/`: Saved server responses for replay.

## Writing Tests

### Unit Test Example

```php
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    public function testSomething(): void
    {
        $this->assertTrue(true);
    }
}
```

### Fixture Replay

Use `FixtureReplayHelper` trait for testing with captured data.

## Capture for Testing

1. Capture a real server: `php bin/capture <ip> <port> <protocol>`
2. Fixtures saved in `tests/fixtures/`
3. Tests replay fixtures for consistent results.
