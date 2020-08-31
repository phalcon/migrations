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
