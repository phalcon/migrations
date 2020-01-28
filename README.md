# Phalcon Migrations

[![Discord](https://img.shields.io/discord/310910488152375297?label=Discord)](http://phalcon.link/discord)
[![codecov](https://codecov.io/gh/phalcon/migrations/branch/master/graph/badge.svg)](https://codecov.io/gh/phalcon/migrations)

Generate or migrate database changes via migrations.  
Main idea of Phalcon migrations is to automatically detect changes and morphing without writing manual migrations.

## Usage

```
use Phalcon\Migrations\Migrations;

$migration = new Migrations();
$migration::run([
    'migrationsDir' => [
        __DIR__ . '/migrations',
    ],
    'config' => [
        'database' => [
            'adapter' => 'Mysql',
            'host' => 'phalcon-db-mysql',
            'username' => 'root',
            'password' => 'root',
            'dbname' => 'vokuro',
        ],
    ]
]);
```

## Quick start

What you need to quick start:

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

## CLI Arguments and options

**Arguments**

| Argument | Description
| -------- | -----------
| generate | Generate a Migration
| run      | Run a Migration
| list     | List all available migrations 

**Options**

| Action | Description
| ------ | -----------
| --action=s | Generates/Runs a Migration [generate|run]
| --config=s | Configuration file
| --migrations=s | Migrations directory. Use comma separated string to specify multiple directories
| --directory=s | Directory where the project was created
| --table=s | Table to migrate. Table name or table prefix with asterisk. Default: all
| --version=s | Version to migrate
| --descr=s   | Migration description (used for timestamp based migration)
| --data=s    | Export data [always|oncreate] (Import data when run migration)
| --exportDataFromTables=s | Export data from specific tables, use comma separated string.
| --force | Forces to overwrite existing migrations
| --ts-based | Timestamp based migration version
| --log-in-db | Keep migrations log in the database table rather then in file
| --dry | Attempt requested operation without making changes to system (Generating only)
| --verbose | Output of debugging information during operation (Running only)
| --no-auto-increment | Disable auto increment (Generating only)
| --help | Shows this help

## Requirements

* PHP >= 7.2
* Phalcon >= 4.0.0

## Installing via Composer

```
composer require --dev phalcon/migrations
```

```json
{
    "require": {
        "phalcon/migrations": "^1.1"
    }
}
```