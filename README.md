# TinyPHP

TinyPHP is a minimalistic PHP framework designed for learning PHP in-depth. It focuses on simplicity, clarity, and adheres to best practices and PSR standards.

## Features

- **PSR-11 Container**: A simple yet powerful service container with auto-wiring support.
- **Latest PHP Features**: Utilizes modern PHP 8.2 features.
- **Clean Codebase**: Easy to read, understand, and extend.
- **Static Analysis**: Successfully passed PHPStan checks at the maximum level, ensuring a robust and bug-resistant codebase.
- **Educational**: A great tool for learning PHP in a hands-on manner.

## Installation

```bash
composer create-project jewei/tinyphp:dev-main myapp

```

## Usage

### Registering Services

```php
$app = new TinyPHP\Application;

// Register a service
$app->set(Config::class, Config::class);

// Retrieve the service
$config = $app->get(Config::class);

```

### Auto-Wiring Dependencies

```php
class Database {
    public function __construct(private Config $config) {}
}

$app->set(Config::class, Config::class);
$app->set(Database::class, Database::class);

// Retrieve the Database service, dependencies will be auto-wired
$db = $app->get(Database::class);
$db->config;

```

## Testing

```bash
composer test

```

## License

TinyPHP is licensed under the MIT License.
