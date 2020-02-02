# Phalcon Migrations

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg)](https://php.net/)
[![Discord](https://img.shields.io/discord/310910488152375297?label=Discord)](http://phalcon.link/discord)
[![codecov](https://codecov.io/gh/phalcon/migrations/branch/master/graph/badge.svg)](https://codecov.io/gh/phalcon/migrations)

Generate or migrate database changes via migrations.  
Main idea of Phalcon migrations is to automatically detect changes and morphing without writing manual migrations.

## Full documentation

https://docs.phalcon.io/latest/en/db-migrations

## Requirements

* PHP >= 7.2
* Phalcon >= 4.0.0
* PHP ext-posix (Linux)

## Installing via Composer

```
composer require --dev phalcon/migrations
```

## Quick start

What you need for quick start:

* Configuration file in root of your project (you can also pass them as parameters inside CLI environment)
* Create database tables structure
* Execute command to generate migrations

After that you can execute that migrations (run) in another environment to create same DB structure.

### Create configuration file

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
        'migrationsDir' => 'db/migrations',
        'migrationsTsBased' => true, // true - Use TIMESTAMP as version name, false - use versions (1.0.1)
        'exportDataFromTables' => [
            // Tables names
            // Attention! It will export data every new migration
        ],
    ],
]);
```

### Generate migrations

```
vendor/bin/phalcon-migrations migration generate
```

### Run Migrations

```
vendor/bin/phalcon-migrations migration run
```