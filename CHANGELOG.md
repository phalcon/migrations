# [Unreleased]

## Added

- Added the `namespace` option (`--namespace` and `application.namespace`) to generate migration classes in a namespace; the runner reads the namespace of each file, so files with and without a namespace both run ([#41](https://github.com/phalcon/migrations/issues/41))
- Added the `log-path` option (`--log-path` and `application.log-path`) for the path of the file log, so that each database can have its own log; a relative path starts in the project directory. The default stays `.phalcon/migration-version` ([#5](https://github.com/phalcon/migrations/issues/5))
- Added the `charset` key of the `database` config (MySQL), with the default `utf8mb4`. Before, the connection always used `utf8` (`utf8mb3`), so 4-byte characters in the data were lost in the export and the import; the README example had a `charset` key that was not read ([#254](https://github.com/phalcon/migrations/issues/254))
- Added the column option `onUpdate` for MySQL `ON UPDATE CURRENT_TIMESTAMP` (`'onUpdate' => 'CURRENT_TIMESTAMP'`): it is read, generated, created and changed. Only the plain form is read (`CURRENT_TIMESTAMP`, or `current_timestamp()` on MariaDB); `CURRENT_TIMESTAMP(6)` needs the precision of the column, which is not read ([#258](https://github.com/phalcon/migrations/issues/258))
- Added the column option `defaultExpression` for a default that is an expression (PostgreSQL), for example `gen_random_uuid()`: the PostgreSQL read sets it for a default in the form of a function call with no quotes, `generate` writes `'defaultExpression' => true`, and the DDL writes the default with no quotes. Before, the default was written as a text (`DEFAULT 'gen_random_uuid()'`), so the create failed on a `UUID` column. The morph does not compare the option, so a file generated before this version works as before ([#113](https://github.com/phalcon/migrations/issues/113))
- Added the `clean` action: `clean --force` drops all the tables (and resets the log, so that the next run starts from the first version), `--table=<names>` drops only these tables, and `--data-only` deletes the rows and keeps the tables. The log table stays. Tables that refer to each other work (MySQL and SQLite turn the foreign key checks off for the action; PostgreSQL uses one statement). New adapter methods `dropTables()` and `emptyTables()` ([#72](https://github.com/phalcon/migrations/issues/72))
- Added the `log-table` option (`--log-table` and `application.log-table`) for the name of the log table, so that two sets of migrations can have their own log in one database; `generate` skips that table, and the SQL of the log quotes its name. The default stays `phalcon_migrations` ([#60](https://github.com/phalcon/migrations/issues/60))
- Added the `truncate` option (`--truncate` and `application.truncate`) for `run`: `batchInsert()` empties the table in its transaction before it loads the data, so a version with data (`--data=always`) does not insert the same rows again over the rows of an earlier version ([#83](https://github.com/phalcon/migrations/issues/83))
- Added the column comments on PostgreSQL: `createTable()`, `addColumn()` and `modifyColumn()` set them with `COMMENT ON COLUMN` (before, the comments were read but never written) ([#89](https://github.com/phalcon/migrations/issues/89))
- Added the MySQL `SET` column type (`Column::TYPE_SET`): it is read, generated and created with its values, as `ENUM`. Before, `generate` wrote a `SET` column as `TYPE_VARCHAR` with a size ([#155](https://github.com/phalcon/migrations/issues/155))
- Added the MySQL `ROW_FORMAT` to the generated table options, when the table sets it (`create_options`), so that a table created from the migration file gets it. The morph does not change the options of a table that exists ([#125](https://github.com/phalcon/migrations/issues/125))

## Fixed

- Fixed `generate` writing the numeric precision as the size of MySQL integer columns (for example `'size' => 10` for `INT` and `'size' => 3` for `TINYINT(1)`); the size is now the display width of the column type, or no size when the server gives none (MySQL 8.0.19+) ([#139](https://github.com/phalcon/migrations/issues/139))
- Fixed a MySQL `TYPE_BOOLEAN` column with a size giving `TINYINT(1)(3)`, a syntax error on create and modify, and the table morph changing every integer column on each run because it compared the size and the scale of integers, which do not change the storage ([#218](https://github.com/phalcon/migrations/issues/218))
- Fixed PostgreSQL `uuid` columns read as `TYPE_VARCHAR`, so `generate` wrote `'size' => 1` and the table morph tried to change the column to `CHARACTER VARYING(1)`; added `Column::TYPE_UUID` (PostgreSQL `UUID`, no size). Generate again the migration files of tables with `uuid` columns ([#105](https://github.com/phalcon/migrations/issues/105))
- Fixed a new table on PostgreSQL and SQLite getting only its primary key; `createTable()` now also creates the other indexes of the definition, as on MySQL ([#221](https://github.com/phalcon/migrations/issues/221))
- Fixed `batchDelete()` failing on a table with no `id` column (a `DELETE ... WHERE id = ...` for each row of the `.dat` file ran after the table was emptied); it now runs one `DELETE FROM`, and still empties the table. Fixed `batchInsert()` leaving its transaction open and the `.dat` file not closed after an error, and the table name not quoted in `batchInsert()` and `batchDelete()` ([#223](https://github.com/phalcon/migrations/issues/223))
- Fixed `generate` putting values into the migration file with no escaping (the default, the comment, the table, column, index and foreign key names, the enum values and the table options), so a quote, a `$` or a backslash gave a file that did not parse or had a different value ([#225](https://github.com/phalcon/migrations/issues/225))
- Fixed the table morph failing to drop a column that has a foreign key (MySQL error 1828); the foreign keys on the dropped columns are now dropped first, also when the file has no `references` key ([#6](https://github.com/phalcon/migrations/issues/6))
- Fixed an absolute `--config` path getting the project directory in front of it, and a missing configuration file loading as an empty configuration with only a PHP warning; a missing file now throws "Can't locate the configuration file." Fixed the README: it listed Phalcon as a requirement, and its configuration example returned a `Phalcon\Config` object, which this version cannot read (with no Phalcon the class does not exist, and an object loads as an empty configuration); the example now returns a plain array ([#21](https://github.com/phalcon/migrations/issues/21))
- Fixed `generate` writing `'size' => 1` for JSON and JSONB columns, splitting an enum value that has a comma into two values and keeping `''` in a value that has a quote, and leaving out the `CURRENT_TIMESTAMP` defaults (MySQL and PostgreSQL). A plain `CURRENT_TIMESTAMP` default (`CURRENT_TIMESTAMP()` on MariaDB) is now read, generated and created; a default with a precision (for example `CURRENT_TIMESTAMP(6)`) or an expression is still left out ([#155](https://github.com/phalcon/migrations/issues/155))
- Fixed `listColumns()` on MySQL and PostgreSQL for a column in more than one key (for example a primary key column that is also a foreign key): the column query joined every key of the table, so the column came back more than one time, lost its primary and first flags, and got `after` with its own name ([#235](https://github.com/phalcon/migrations/issues/235))
- Fixed the MySQL table morph sending a `CHANGE COLUMN` on each run for the primary key column (the morph compared the primary flag, which a MySQL file does not have on the column; the index sync changes the primary key) and for each `ENUM` and `SET` column (the read gave a size, the file has none). The morph now compares the values of an `ENUM` or a `SET`, which it did not do before ([#237](https://github.com/phalcon/migrations/issues/237))
- Fixed two bugs of the PostgreSQL adapter: a `SERIAL` column was read with the default `0` (from `nextval(...)`), so the morph sent an `ALTER COLUMN` for each serial column on each run; and `dropIndex()` quoted `schema.index` as one name, so the drop of a changed index did nothing and the add failed (`relation ... already exists`) ([#239](https://github.com/phalcon/migrations/issues/239))
- Fixed the foreign keys to a table in another schema and the composite foreign keys: PostgreSQL `listReferences()` gave the schema of the table as the referenced schema, and the columns of a composite foreign key more than one time (`[x, x, y, y]`); and the morph added and dropped a foreign key on a table in the referenced schema, not in the schema of the table ([#241](https://github.com/phalcon/migrations/issues/241))
- Fixed a short version getting one part too many (`1.1` gave `1.1.0.0`, so `generate --version=1.1` created the folder `1.1.0.0`), and the versions of a second migrations folder being lost in the next version of `generate` and in the table of `list` (the lists were joined with `+`) ([#243](https://github.com/phalcon/migrations/issues/243))
- Fixed `generate` for all the tables writing the data of the log table (`phalcon_migrations.dat`, or the `--log-table`) with `--data`; `Migration::generateAll()` has a new optional argument with the tables to skip ([#245](https://github.com/phalcon/migrations/issues/245))
- Fixed the help: it did not list the `--skip-ref-schema` and `--skip-foreign-checks` options ([#247](https://github.com/phalcon/migrations/issues/247))
- Fixed a new SQLite table getting no foreign keys: SQLite can create a foreign key only in the `CREATE TABLE`, and the `CREATE TABLE` left out the `references` of the definition ([#249](https://github.com/phalcon/migrations/issues/249))
- Fixed `--dry`: `generate --dry` with `--data` (or `--exportDataFromTables`) still exported the data, into a folder that the dry run did not create (a `TypeError`); now a dry run writes no data file. `run --dry` ran the migrations for real; now it stops with the error "The --dry option is for generate only." ([#252](https://github.com/phalcon/migrations/issues/252))
- Fixed the DB log turning off the MySQL foreign key checks on its own connection (`SET FOREIGN_KEY_CHECKS=0`), which had no effect on the migrations; use `--skip-foreign-checks` to turn the checks off for a run ([#254](https://github.com/phalcon/migrations/issues/254))
- Fixed `composer.json` having the version `4.0.0` after the release of `v4.0.1`; the `version` field is removed, and the tags give the version ([#256](https://github.com/phalcon/migrations/issues/256))
- Fixed the last foreign key or index of a table staying in the database: `generate` left out the `references` and `indexes` keys when the table had none, so the morph did not sync them; the keys are now always in the file, as empty lists ([#227](https://github.com/phalcon/migrations/issues/227))

## Changed

- Changed the table morph to compare the `onUpdate` of a column: a migration file generated before this version has no `onUpdate`, so it removes `ON UPDATE CURRENT_TIMESTAMP` from the live column (a `CHANGE COLUMN` for another change already did this before). Generate again the versions that did not run yet ([#258](https://github.com/phalcon/migrations/issues/258))
- Changed the table morph to see the `CURRENT_TIMESTAMP` defaults: a migration file generated before this version has no default for such a column, so it removes the default from the live column. Generate again the versions that did not run yet ([#155](https://github.com/phalcon/migrations/issues/155))
- Changed the table morph to compare the column comment: a change of only the comment is now applied, and a column with no comment in the migration file clears the comment of the live column (on MySQL, this happened already when another part of the column changed) ([#89](https://github.com/phalcon/migrations/issues/89))
- Changed the generated migration files: they have `declare(strict_types=1)`, and a `TABLE_NAME` class constant that `morphTable()`, `batchInsert()` and `batchDelete()` use, in place of the table name string ([#87](https://github.com/phalcon/migrations/issues/87), [#107](https://github.com/phalcon/migrations/issues/107))
- Changed the source and test files to import the global functions and constants that they use (`use function`, `use const`); no change to the code ([#250](https://github.com/phalcon/migrations/issues/250))
- Changed CI to run php-cs-fixer (dry run) in the code style job on PHP 8.2 - 8.5 (php-cs-fixer does not support PHP 8.6 yet), and fixed the order of the test methods in `OptionStackTest` and `VersionLogTest` ([#216](https://github.com/phalcon/migrations/issues/216))

# [4.0.1](https://github.com/phalcon/migrations/releases/tag/v4.0.1) (2026-11-13)

## Fixed

- Fixed versions being ordered as strings instead of naturally in the `.phalcon/migration-version` file and when reading the latest version from the migrations log table, so `1.0.10` was listed before `1.0.2` and `1.0.9` was reported as current over `1.0.10` ([#181](https://github.com/phalcon/migrations/issues/181))
- Fixed migrations log table creation failing on MySQL servers with utf8mb4 and MyISAM or older InnoDB row formats by shrinking the `version` primary key column from `VARCHAR(255)` to `VARCHAR(191)` ([#148](https://github.com/phalcon/migrations/issues/148))
- Fixed `generate` ignoring the `--ts-based` option and the `migrationsTsBased` config: it now creates a timestamp-based version without requiring `--descr`, and an empty `--descr` no longer produces an invalid version name ([#144](https://github.com/phalcon/migrations/issues/144))

# [4.0.0](https://github.com/phalcon/migrations/releases/tag/v4.0.0) (2026-11-08)

## Added

- Added `@template-implements ArrayAccess<string, mixed>` annotation to `OptionStack` (psalm compliance) ([#167](https://github.com/phalcon/migrations/issues/167))
- Added `Phalcon\Migrations\Db\Adapter\Mysql`, `Postgresql`, `Sqlite` - schema introspection (columns, indexes, foreign keys, table options) and full DDL generation per driver ([#167](https://github.com/phalcon/migrations/issues/167))
- Added `Phalcon\Migrations\Db\Column`, `Index`, `Reference` value objects replacing their `Phalcon\Db` counterparts ([#167](https://github.com/phalcon/migrations/issues/167))
- Added `Phalcon\Migrations\Db\Connection` - trimmed PDO wrapper covering all database operations ([#167](https://github.com/phalcon/migrations/issues/167))
- Added `Phalcon\Migrations\Db\PhalconColumnBridge` for transparent backward compatibility: migration files using `Phalcon\Db\Column` continue to work without modification ([#167](https://github.com/phalcon/migrations/issues/167))
- Added `Phalcon\Utils\Camelize` to be used instead of the one from Phalcon ([#167](https://github.com/phalcon/migrations/issues/167))
- Added `migration migrate-files` CLI command to update existing migration files from `Phalcon\Db` to `Phalcon\Migrations\Db` namespace ([#167](https://github.com/phalcon/migrations/issues/167))
- Added `tests/Fakes/` directory with `MigrationFake` and `MigrationCommandFake` test helpers; registered `Phalcon\Migrations\Tests\Fakes\` PSR-4 namespace in `composer.json` ([#167](https://github.com/phalcon/migrations/issues/167))
- Added `tests/unit/Sqlite/AdapterTest.php` covering `tableExists()`, primary key handling, function defaults, and index listing against an in-memory SQLite database, plus `current_timestamp()` default tests for the `Mysql` and `Postgresql` adapters ([#176](https://github.com/phalcon/migrations/issues/176))
- Added backwards-compatibility fixtures in `tests/_data/backcompat/old/` and `tests/_data/backcompat/new/` covering old-format (`Phalcon\Db\*`) and new-format (`Phalcon\Migrations\Db\*`) migrations across two versions each (create + alter column) ([#167](https://github.com/phalcon/migrations/issues/167))
- Added migration script to migrate existing migrations to new references; provision for running either migrations (old/refactored) ([#167](https://github.com/phalcon/migrations/issues/167))
- Added rollback tests exercising `DIRECTION_BACK` and `createPrevClassWithMorphMethod` to verify table structure is correctly restored when running migrations backwards ([#167](https://github.com/phalcon/migrations/issues/167))
- Added support for PHP 8.6; CI now runs code style, static analysis and tests on PHP 8.2 - 8.6 ([#198](https://github.com/phalcon/migrations/pull/198))

## Fixed
 
- Fixed PHPStan errors: removed dead `$map` property from `AdapterFactory`, corrected `@var SplFileInfo` annotation, removed `@method` annotations from `Migration` class docblock that caused `method_exists()` to always evaluate to true, removed redundant `@var Config` annotation that made an `instanceof` guard appear always-true ([#167](https://github.com/phalcon/migrations/issues/167))
- Fixed Psalm errors: unused `$reference` and `$indexColumns` foreach values replaced with `$_`, unused `$description` in first loop of `printParameters()` replaced with `$_`, `realpath()` false return handled explicitly, implicit `__toString` casts made explicit with `(string)`, mixed int/float arithmetic in `IncrementalItem` made type-safe with `(int) pow()` and intermediate `$multiplier` variable, `addMinor()` string `+=` int fixed with explicit `(string)((int) $parts[0] + $number)` ([#167](https://github.com/phalcon/migrations/issues/167))
- Fixed `Mysql` and `Postgresql` adapters quoting the MySQL/MariaDB-style `current_timestamp()` default spelling as a string literal; function-default recognition is now shared across all adapters via `AbstractAdapter::isCurrentTimestampDefault()`, PostgreSQL normalises the parenthesized spelling it does not accept and also applies function defaults in the `modifyColumn()` `SET DEFAULT` path ([#176](https://github.com/phalcon/migrations/issues/176))
- Fixed `PhalconColumnBridge::fromPhalcon()` preserving an empty-string `getAfterPosition()` from `Phalcon\Db\Column` instead of normalising it to `null`; this caused `AFTER `` ` in the generated ALTER SQL when adding columns from old-format migrations ([#167](https://github.com/phalcon/migrations/issues/167))
- Fixed `Sqlite::buildCreateTableSql()` silently dropping primary keys declared with the generated-migration `PRIMARY` index convention (name `PRIMARY` / type `PRIMARY`); a table-level `PRIMARY KEY` clause is now emitted unless a column already renders as `INTEGER PRIMARY KEY AUTOINCREMENT` ([#176](https://github.com/phalcon/migrations/issues/176))
- Fixed `Sqlite::listIndexes()` reporting internal `sqlite_autoindex_*` entries, which the table morph then attempted (and failed) to drop ([#176](https://github.com/phalcon/migrations/issues/176))
- Fixed `Sqlite` adapter failing with `near "PRIMARY": syntax error` during table morph; SQLite cannot add a primary key via `ALTER TABLE`, so `addPrimaryKey()` is now a no-op consistent with the other unsupported SQLite DDL operations ([#176](https://github.com/phalcon/migrations/issues/176))
- Fixed `Sqlite` adapter inheriting the `information_schema.tables` based `tableExists()`, which made every migration run against SQLite fail with `no such table: information_schema.tables`; the existence check now queries `sqlite_master` ([#176](https://github.com/phalcon/migrations/issues/176))
- Fixed `Sqlite` adapter storing function defaults as literal strings; `CURRENT_TIMESTAMP`, `CURRENT_TIMESTAMP()`, and `NOW()` are now emitted as the bare `CURRENT_TIMESTAMP` keyword ([#176](https://github.com/phalcon/migrations/issues/176))
- Fixed `morphTable()` passing the original `$definition` array (containing unconverted `Phalcon\Db\Column`, `Index`, `Reference` objects) to `createTable()`; objects are now normalised to `Phalcon\Migrations\Db\*` types in-place during the column conversion loop, so old-format migration files work correctly on table creation ([#167](https://github.com/phalcon/migrations/issues/167))
- Fixed four `\$this` syntax errors in `tests/unit/Cli/RunTest.php` that prevented the test suite from loading ([#167](https://github.com/phalcon/migrations/issues/167))
- Fixed typo in `Generate::checkEntityExists()` error message: `'Migration entity is e,pty'` → `'Migration entity is empty'` ([#167](https://github.com/phalcon/migrations/issues/167))
- Fixed `.phar` compilation after moving the CLI entry point to `bin/phalcon-migrations`; `box.json` no longer lists the removed root script and writes `phalcon-migrations.phar` to the project root as the release workflow expects ([#199](https://github.com/phalcon/migrations/pull/199))

## Changed

- Added named static constructor methods to all exception classes returning `self`, eliminating raw `new ExceptionClass('literal string')` throw sites across the codebase ([#167](https://github.com/phalcon/migrations/issues/167))
- Changed Config file loading (`.ini`, `.json`, `.yaml`) to now use native PHP functions (`parse_ini_file`, `json_decode`, `yaml_parse_file`) instead of `Phalcon\Config` adapters ([#167](https://github.com/phalcon/migrations/issues/167))
- Changed `Observer/Profiler` and `Listeners/DbProfilerListener` without `Phalcon\Db` dependency; query timing output is unchanged ([#167](https://github.com/phalcon/migrations/issues/167))
- Changed minimum PHP version to 8.2 ([#167](https://github.com/phalcon/migrations/issues/167))
- Changed test suite from Codeception to PHPUnit 11.5; all tests moved to a single `unit` suite under `tests/unit/` ([#167](https://github.com/phalcon/migrations/issues/167))
- Changed `vimeo/psalm` to `^7.1`, which supports PHP 8.6; Psalm 7 reports code reached only through the public API as unused, so `PossiblyUnusedProperty`, `UnusedProperty`, `UnusedMethod` and `UnusedConstructor` are suppressed alongside `PossiblyUnusedMethod` ([#198](https://github.com/phalcon/migrations/pull/198))
- Configured PHPStan at level 5 (embedded in `phpstan.neon.dist`); zero errors ([#167](https://github.com/phalcon/migrations/issues/167))
- Configured Psalm (`psalm.xml.dist`) with suppressions for `MissingOverrideAttribute` (PHP 8.3 only, project targets 8.2), `PossiblyUnusedMethod`/`UnusedClass` (library public API), `PossiblyNullArrayOffset` (`isset` is safe with null keys), `PossiblyInvalidCast` (Psalm cannot track the conditional `$storage` field type), and `RedundantCast` (Psalm contradicts itself on `pow()` return type narrowing) ([#167](https://github.com/phalcon/migrations/issues/167))
- Expanded test suite from 91 to 342 tests; added test classes for `Utils\Config`, `Utils\Helper`, `Version\IncrementalItem`, `Version\ItemCollection`, `Version\TimestampedItem`, `Console\Color`, `Console\OptionStack`, `Console\Commands\MigrateFiles`, `Console\Commands\Migration`, `Generator\Snippet`, all `Exception` classes, `Observer\Profiler`, `Listeners\DbProfilerListener`, `Db\PhalconColumnBridge`, `Db\FieldDefinition`, `Db\Adapter\Mysql`, `Db\Adapter\Postgresql`, `Db\Connection`, `Migration\Action\Generate`, `Mvc\Model\Migration`, and `Mvc\Model\Migration\TableAware\ListTablesIterator` ([#167](https://github.com/phalcon/migrations/issues/167))
- Moved CLI entry point from project root to `bin/phalcon-migrations` ([#167](https://github.com/phalcon/migrations/issues/167))
- `TimestampedItem`, `ListTablesDb`, `ListTablesIterator`, `Connection`, and `AdapterFactory` now use the custom `Phalcon\Migrations\Exception\InvalidArgumentException` instead of the PHP built-in, giving the library a consistent exception hierarchy ([#167](https://github.com/phalcon/migrations/issues/167))

## Removed

- Removed `Phalcon\Config\Config` dependency; replaced with `Phalcon\Migrations\Utils\Config` flat value object ([#166](https://github.com/phalcon/migrations/issues/166))
- Removed `Phalcon\Db` dependency entirely; replaced with library-local `Phalcon\Migrations\Db` layer ([#166](https://github.com/phalcon/migrations/issues/166))

# [2.2.4](https://github.com/phalcon/migrations/releases/tag/v2.2.4) (2021-12-10)
- Changed column modification behavior during table morph ([#126](https://github.com/phalcon/migrations/issues/126))

# [2.2.3](https://github.com/phalcon/migrations/releases/tag/v2.2.3) (2021-08-11)
- Changed column modification behavior during table morph ([#97](https://github.com/phalcon/migrations/issues/97))
- Updated composer packages ([#124](https://github.com/phalcon/migrations/pull/124))

# [2.2.2](https://github.com/phalcon/migrations/releases/tag/v2.2.2) (2021-08-08)
- Integrated nette/php-generator, changed algorithm of migrations generation ([#90](https://github.com/phalcon/migrations/issues/90))

# [2.2.1](https://github.com/phalcon/migrations/releases/tag/v2.2.1) (2021-08-03)
- Fixed types and indexes definition on pgsql adapter ([#111](https://github.com/phalcon/migrations/issues/111), [#112](https://github.com/phalcon/migrations/issues/112), [#118](https://github.com/phalcon/migrations/issues/118))

# [2.2.0](https://github.com/phalcon/migrations/releases/tag/v2.2.0) (2021-08-03)
Supported Versions: 7.3, 7.4, 8.0

- This release provides initial PHP 8 support.

# [2.1.6](https://github.com/phalcon/migrations/releases/tag/v2.1.6) (2021-03-22)
- Fixed duplicated table `phalcon_migrations` in PostgreSQL ([#104](https://github.com/phalcon/migrations/issues/104))
- Fixed morphing when column attribute `unsiged` is changed ([#109](https://github.com/phalcon/migrations/issues/109))


# [2.1.5](https://github.com/phalcon/migrations/releases/tag/v2.1.5) (2020-11-03)
- Enhanced error output during failed table morph ([#99](https://github.com/phalcon/migrations/issues/94))


# [2.1.4](https://github.com/phalcon/migrations/releases/tag/v2.1.4) (2020-08-31)
- Fixed 'options' table definition ([#94](https://github.com/phalcon/migrations/issues/94))


# [2.1.3](https://github.com/phalcon/migrations/releases/tag/v2.1.3) (2020-08-29)
- Improved tests codebase ([#86](https://github.com/phalcon/migrations/issues/86))
- Fixed `.phar` compilation ([#91](https://github.com/phalcon/migrations/issues/85))
- Added filter for column default value ([65735c1](https://github.com/phalcon/migrations/commit/65735c1e894549ccb5d7dc56749bf6a929c8351f))


# [2.1.2](https://github.com/phalcon/migrations/releases/tag/v2.1.2) (2020-03-30)
Supported PHP versions: 7.2, 7.3, 7.4

- Added separate changelog document `CHANGELOG.md` ([#85](https://github.com/phalcon/migrations/issues/85))
- Fixed long execution of data insertion during migration run (x14 faster) ([#76](https://github.com/phalcon/migrations/issues/76))
- Added `README.md` inside tests folder ([#78](https://github.com/phalcon/migrations/issues/78))


# [2.1.1](https://github.com/phalcon/migrations/releases/tag/v2.1.1) (2020-03-26)
Supported PHP versions: 7.2, 7.3, 7.4

- Added size for `DECIMAL` Column type (MySQL) ([#77](https://github.com/phalcon/migrations/pull/77))
- Added possibility to specify options `no-auto-increment`, `skip-ref-schema` and `skip-foreign-checks` from config file ([#80](https://github.com/phalcon/migrations/pull/80))


# [2.1.0](https://github.com/phalcon/migrations/releases/tag/v2.1.0) (2020-03-22)
Supported PHP versions: 7.2, 7.3, 7.4

- Added option to disable and enable foreign key ([#11](https://github.com/phalcon/migrations/pull/11))
- Added config option to ignore reference schema ([#68](https://github.com/phalcon/migrations/pull/68))
- Added more tests ([38028e8](https://github.com/phalcon/migrations/commit/38028e879355d90b2e5f5203968886cb5bc355b6))
- Removed extended class of Phalcon PDO and Dialect ([58c383b](https://github.com/phalcon/migrations/commit/58c383bee088c2a2bb950a4aeb1dd81a542ad6a4))


# [2.0.0](https://github.com/phalcon/migrations/releases/tag/v2.0.0) (2020-03-19)
- Integrated Phalcon CLI Options Parser (COP) ([#20](https://github.com/phalcon/migrations/pull/20))
- Removed duplicated required 1st argument `migration` in CLI ([#20](https://github.com/phalcon/migrations/pull/20))
- Rewrote all tests from PHPUnit to Codeception, also added CLI tests ([#20](https://github.com/phalcon/migrations/pull/20), [2ede70c](https://github.com/phalcon/migrations/commit/2ede70c9aaf1ecc04e425eeeee54a5f8d818bd01))


# [1.2.2](https://github.com/phalcon/migrations/releases/tag/v1.2.2) (2020-03-18)
- Fixed references generation ([d225f61](https://github.com/phalcon/migrations/commit/d225f6188a4ca648033203553dd13e26ef43bd00))


# [1.2.1](https://github.com/phalcon/migrations/releases/tag/v1.2.1) (2020-03-13)
- Fixed running migration that were added before the latest ([#66](https://github.com/phalcon/migrations/issues/66))
- Refactored part of Migration file generation


# [1.2.0](https://github.com/phalcon/migrations/releases/tag/v1.2.0) (2020-03-12)
- Disabled AUTO_INCREMENT option in migrations by default ([#57](https://github.com/phalcon/migrations/issues/57))
- Added PRIMARY KEY in table "phalcon_migrations" on column "version" ([#58](https://github.com/phalcon/migrations/issues/58))
- Implement PHAR release for each new version ([#12](https://github.com/phalcon/migrations/issues/12))
- Updated shivammathur/setup-php to v2 and add cache for extensions ([5ce0c6a](https://github.com/phalcon/migrations/commit/5ce0c6adcffb04c863b9fb5a3244074a22e48129))


# [1.1.7](https://github.com/phalcon/migrations/releases/tag/v1.1.7) (2020-02-10)
- Added support of ENUM column type ([#7](https://github.com/phalcon/migrations/issues/7))


# [1.1.6](https://github.com/phalcon/migrations/releases/tag/v1.1.6) (2020-02-06)
- Updated list of column types without `size` ([#49](https://github.com/phalcon/migrations/issues/49))
- Fixed generation table columns with NULL definition ([#51](https://github.com/phalcon/migrations/issues/51))
- Fixed running time based migrations ([#53](https://github.com/phalcon/migrations/issues/53))


# [1.1.5](https://github.com/phalcon/migrations/releases/tag/v1.1.5) (2020-02-02)
- Fixed adding primary key in migration generation for PostgreSQL ([#1](https://github.com/phalcon/migrations/issues/1))
- Added test cases in migrations to run `SET FOREIGN_KEY_CHECKS` ([#2](https://github.com/phalcon/migrations/issues/2))
- Implemented workflows for PostgreSQL with new tests ([#43](https://github.com/phalcon/migrations/issues/43))
- Added support of 'descr' option in config file ([#39](https://github.com/phalcon/migrations/issues/39))
- Fixed PHP Notice in case if migrations directory(ies) was(were) not found ([#40](https://github.com/phalcon/migrations/issues/40))
- Adapt code to PSR-12 format ([#47](https://github.com/phalcon/migrations/issues/47))


# [1.1.4](https://github.com/phalcon/migrations/releases/tag/v1.1.4) (2020-01-29)
- Updated Github Actions to run tests with different PHP versions: 7.2, 7.3, 7.4 ([#32](https://github.com/phalcon/migrations/issues/32))
- Updated minimum required version of Phalcon to `4.0.0` ([b3e1a4a](https://github.com/phalcon/migrations/commit/b3e1a4aa3d31abe190fcb79c01e620e935b44d37))
- Reviewed support of MySQL `JSON` type ([#3](https://github.com/phalcon/migrations/issues/3))
- Fixed adding foreign key during separate migrations ([#29](https://github.com/phalcon/migrations/issues/29))


# [1.1.3](https://github.com/phalcon/migrations/releases/tag/v1.1.3) (2020-01-01)
- Added support of `TIME` datatype ([#8](https://github.com/phalcon/migrations/issues/8))


# [1.1.2](https://github.com/phalcon/migrations/releases/tag/v1.1.2) (2019-12-30)
- Minor refactor ([c7cf72a](https://github.com/phalcon/migrations/commit/c7cf72a665066ca5816fa03df0cc3b8eeeb58686))
- Fixed 2 argument type in `batchInsert()` method ([63f5b33](https://github.com/phalcon/migrations/commit/63f5b335a101fb33be115576aacf6576ab3f91ef))


# [1.1.1](https://github.com/phalcon/migrations/releases/tag/v1.1.1) (2019-12-21)
- Added support of previous unexisted column types: BIGINT, MEDIUMINT, TINYINT, MEDIUMMEXT, LONGTEXT, TINYTEXT ([#24](https://github.com/phalcon/migrations/issues/24))
- Fix size for DOUBLE column type ([543a010](https://github.com/phalcon/migrations/commit/543a01006bb0e3d09f9edfd02fcf8051b403caac), [phalcon/phalcon-devtools#1399](https://github.com/phalcon/phalcon-devtools/issues/1399))


# [1.1.0](https://github.com/phalcon/migrations/releases/tag/v1.1.0) (2019-12-14)
- Refactored Migrations class ([9603ab7](https://github.com/phalcon/migrations/commit/9603ab714df01bc2c0159c87434fc0caab6c77fc), [24a62fa](https://github.com/phalcon/migrations/commit/24a62facaa01236ba4a64d6e3681d0cc15643c9f))
- Implemented bin for CLI usage ([#13](https://github.com/phalcon/migrations/issues/13))
- Fixed global installation of package ([#15](https://github.com/phalcon/migrations/issues/15))
- Fixed `---log-in-db` parameter ([#17](https://github.com/phalcon/migrations/issues/17))
- Fixed omitting size for DATE and DATETIME types ([#22](https://github.com/phalcon/migrations/issues/22))


# [1.0.4](https://github.com/phalcon/migrations/releases/tag/v1.0.4) (2019-11-18)
- Fixed migrations run with 'migrationsDir' option as a string


# [1.0.3](https://github.com/phalcon/migrations/releases/tag/v1.0.3) (2019-11-18)
- Fixed passing 'migrationsDir' option as a string
- Fixed usage of correct [phalcon/ide-stubs:v4.0.0-rc.3](https://github.com/phalcon/ide-stubs/releases/tag/v4.0.0-rc.3) version


# [1.0.2](https://github.com/phalcon/migrations/releases/tag/v1.0.2) (2019-11-17)
- Removed ext-posix from composer requirements


# [1.0.1](https://github.com/phalcon/migrations/releases/tag/v1.0.1) (2019-11-17)
- Configured Github Actions
- Fixed passing third parameter to generateAll() method
- Refactored current Tests
- Implemented new Tests
- Enabled Codecov.io reports
- Small refactors in code
- Moved Phinx composer package to suggestions
- Bumped minimal Phalcon version to `4.0.0-RC.3`


# [1.0.0](https://github.com/phalcon/migrations/releases/tag/v1.0.0) (2019-10-31)
- First release, separated from devtools
