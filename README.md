# Phalcon Migrations

[![Discord](https://img.shields.io/discord/310910488152375297?label=Discord)](http://phalcon.link/discord)
[![Packagist Version](https://img.shields.io/packagist/v/phalcon/migrations)](https://packagist.org/packages/phalcon/migrations)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/phalcon/migrations)](https://packagist.org/packages/phalcon/migrations)
[![codecov](https://codecov.io/gh/phalcon/migrations/branch/master/graph/badge.svg)](https://codecov.io/gh/phalcon/migrations)
[![Packagist](https://img.shields.io/packagist/dd/phalcon/migrations)](https://packagist.org/packages/phalcon/migrations/stats)

Generate or migrate database changes via migrations.  
Main idea of Phalcon migrations is to automatically detect changes and morphing without writing manual migrations.

## Full documentation

[Phalcon Documentation - Database Migrations](https://docs.phalcon.io/latest/en/db-migrations)

## Requirements

*  PHP >= 7.2
*  Phalcon >= 4.0.5
*  PHP ext-posix (Linux)

## Installing via Composer

```
composer require --dev phalcon/migrations
```

## Quick start

What you need for quick start:

*  Configuration file (ex: `migrations.php`) in root of your project (you can also pass them as parameters inside CLI environment)
*  Create database tables structure
*  Execute command to generate migrations

After that you can execute that migrations (run) in another environment to create same DB structure.

### Create configuration file

Configuration filename can be whatever you want.

```php
<?php

use Phalcon\Config;

return new Config([
    'database' => [
        'adapter' => 'mysql',
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => '',
        'dbname' => 'db-name',
        'charset' => 'utf8',
    ],
    'application' => [
        'logInDb' => true,
        'no-auto-increment' => true,
        'skip-ref-schema' => true,
        'skip-foreign-checks' => true,
        'migrationsDir' => 'db/migrations',
        'migrationsTsBased' => true, // true - Use TIMESTAMP as version name, false - use versions
        'exportDataFromTables' => [
            // Tables names
            // Attention! It will export data every new migration
        ],
    ],
]);
```

### Generate migrations

```bash
vendor/bin/phalcon-migrations generate
```

Or if you have ready to use configuration file.
```bash
vendor/bin/phalcon-migrations generate --config=migrations.php
```

### Run migrations

```bash
vendor/bin/phalcon-migrations run
```

Or if you have ready to use configuration file.
```bash
vendor/bin/phalcon-migrations run --config=migrations.php
```

### List existing migrations

```bash
vendor/bin/phalcon-migrations list
```
