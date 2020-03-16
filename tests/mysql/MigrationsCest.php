<?php

declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Mysql;

use Codeception\Example;
use MysqlTester;
use PDO;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Db\Column;
use Phalcon\Migrations\Migrations;

use function count;

/**
 * @method Config getMigrationsConfig()
 * @method AbstractPdo getPhalconDb()
 * @method PDO getDb()
 */
final class MigrationsCest
{
    /**
     * @throws \Phalcon\Migrations\Script\ScriptException
     * @throws \Phalcon\Mvc\Model\Exception
     */
    public function runAllMigrations(MysqlTester $I): void
    {
        $this->runIssue66Migrations($I);

        $I->seeNumRecords(4, 'phalcon_migrations');
    }

    public function specificMigrationsDataProvider(): array
    {
        return [
            ['0.0.1'],
            ['0.0.2', '0.0.3'],
            ['0.0.2', '0.0.3', '0.0.4'],
            ['0.0.1', '0.0.3', '0.0.4'],
            ['0.0.1', '0.0.4'],
            ['0.0.4'],
        ];
    }

    /**
     * @dataProvider specificMigrationsDataProvider
     *
     * @param array $completedVersion
     * @throws \Phalcon\Migrations\Script\ScriptException
     * @throws \Phalcon\Mvc\Model\Exception
     */
    public function testRunSpecificMigrations(MysqlTester $I, Example $example): void
    {
        $versions = current($example);
        $this->insertCompletedMigrations($I, $versions);

        $I->seeNumRecords(count($versions), 'phalcon_migrations');

        $this->runIssue66Migrations($I);

        $I->seeNumRecords(4, 'phalcon_migrations');
    }

    /**
     * @throws \Phalcon\Migrations\Script\ScriptException
     * @throws \Phalcon\Mvc\Model\Exception
     */
    protected function runIssue66Migrations(MysqlTester $I): void
    {
        ob_start();

        Migrations::run([
            'migrationsDir' => codecept_data_dir('issues/66'),
            'config' => $I->getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);

        ob_clean();
    }

    /**
     * @param array $versions
     */
    protected function insertCompletedMigrations(MysqlTester $I, array $versions): void
    {
        $I->getPhalconDb()->createTable(Migrations::MIGRATION_LOG_TABLE, '', [
            'columns' => [
                new Column(
                    'version',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'size' => 255,
                        'notNull' => true,
                        'first' => true,
                        'primary' => true,
                    ]
                ),
                new Column(
                    'start_time',
                    [
                        'type' => Column::TYPE_TIMESTAMP,
                        'notNull' => true,
                        'default' => 'CURRENT_TIMESTAMP',
                    ]
                ),
                new Column(
                    'end_time',
                    [
                        'type' => Column::TYPE_TIMESTAMP,
                        'notNull' => true,
                        'default' => 'CURRENT_TIMESTAMP',
                    ]
                )
            ],
        ]);

        $date = date('Y-m-d H:i:s');
        foreach ($versions as $version) {
            $sql = sprintf(
                'INSERT INTO phalcon_migrations (version, start_time, end_time) VALUES ("%s", "%s", "%s")',
                $version,
                $date,
                $date
            );
            $I->getPhalconDb()->execute($sql);
        }
    }
}
