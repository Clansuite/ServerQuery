# Installation

## Requirements

- PHP 8.2 or higher (see [supported versions](https://www.php.net/supported-versions.php))
- Composer (dependency manager for PHP)

## Installing via Composer

1. Ensure Composer is installed on your system. If not, download and install it from [getcomposer.org](https://getcomposer.org/).

2. In your project directory, run the following command to add Clansuite Server Query as a dependency:

   ```bash
   composer require clansuite/gameserver-query
   ```

   This will download the library and its dependencies into your `vendor/` directory.

3. Include the autoloader in your PHP scripts:

   ```php
   require_once 'vendor/autoload.php';
   ```

## Verifying Installation

After installation, you can verify that the library is working by running
a simple query. See the [Usage](usage.md) section for examples.

## Optional: Web Interface Setup

If you plan to use the web interface:

1. Copy `serializer.php` from the library's root to your web server's document root.
2. Ensure your web server has PHP support enabled.
3. Access the interface via your browser (e.g., `http://localhost/serializer.php`).

## Troubleshooting Installation

- **Composer Errors**: Ensure your PHP version meets the requirements. Run `php --version` to check.
- **Autoload Issues**: If classes are not found, run `composer dump-autoload` to regenerate the autoloader.
