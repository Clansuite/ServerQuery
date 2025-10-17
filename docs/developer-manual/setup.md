# Setup for Developers

## Environment Requirements

- PHP 8.2 or higher
- Composer
- Git
- (Optional) Docker for isolated testing

## Cloning and Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/Clansuite/ServerQuery.git
   cd Clansuite-Gameserver-Query
   ```

2. Install dependencies:

   ```bash
   composer install
   ```

3. Verify setup:

   ```bash
   composer tests-fast
   ```

## Development Tools

- **PHPUnit**: Run `composer tests-fast` for unit tests.
- **PHP-CS-Fixer**: Code style checking via `composer phpcs-dry`.
- **PHPStan**: Static analysis (if configured).

## Configuration

- Edit `config/capture_config.php` for capture settings.
- Update `composer.json` for autoload paths if extending.

## Running Examples

```bash
php examples/Quake3_test.php
```
