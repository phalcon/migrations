<?php

declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Mysql;

use MysqlTester;
use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\Index;
use Phalcon\Migrations\Migrations;

/**
 * @see https://github.com/phalcon/migrations/issues/94
 */
final class Issue94Cest
{
    /**
     * @param MysqlTester $I
     * @throws Exception
     */
    public function testIssue94(MysqlTester $I): void
    {
        $I->wantToTest('Issue #94 - Engine "MEMORY"');

        ob_start();
        Migrations::run([
            'migrationsDir' => codecept_data_dir('issues/94'),
            'config' => $I->getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_clean();

        $options = $I->getPhalconDb()->tableOptions('memory_table');

        $I->assertSame('MEMORY', $options['engine']);
    }

    /**
     * @param MysqlTester $I
     * @throws Exception
     */
    public function testGenerateIssue94(MysqlTester $I): void
    {
        $I->wantToTest('Issue #94 - Correct options generation case (uppercase)');

        $engine = 'MyISAM';
        $tableName = 'options_uppercase';
        $migrationsDir = codecept_output_dir(__FUNCTION__);

        $I->getPhalconDb()->createTable($tableName, '', [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 20,
                    'notNull' => true,
                    'autoIncrement' => true,
                ]),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], 'PRIMARY')
            ],
            'options' => [
                'TABLE_TYPE' => 'BASE TABLE',
                'ENGINE' => $engine,
                'TABLE_COLLATION' => 'utf8mb4_general_ci',
            ],
        ]);

        /**
         * Generate | Drop | Run
         */
        ob_start();
        Migrations::generate([
            'migrationsDir' => $migrationsDir,
            'config' => $I->getMigrationsConfig(),
            'tableName' => $tableName,
        ]);
        $I->getPhalconDb()->dropTable($tableName);
        Migrations::run([
            'migrationsDir' => $migrationsDir,
            'config' => $I->getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_clean();

        $I->assertSame($engine, $I->getPhalconDb()->tableOptions($tableName)['engine']);
    }
}
