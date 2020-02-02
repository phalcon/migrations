<?php
declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Integration\PostgreSQL;

use Phalcon\Db\Column;
use Phalcon\Migrations\Migrations;
use function Phalcon\Migrations\Tests\root_path;

final class IssuesTest extends PostgreSQLIntegrationTestCase
{
    public function testIssue1(): void
    {
        $tableName = 'table_primary_test';
        $migrationsDir = $migrationsDir = root_path('tests/var/output/' . __FUNCTION__);

        $this->db->createTable($tableName, $this->defaultSchema, [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER,
                    'notNull' => true,
                    'first' => true,
                    'primary' => true,
                ]),
            ],
        ]);

        /**
         * Generate | Drop | Run
         */
        Migrations::generate([
            'migrationsDir' => $migrationsDir,
            'config' => self::$generateConfig,
            'tableName' => $tableName,
        ]);
        $this->db->query('DROP TABLE ' . $tableName);
        Migrations::run([
            'migrationsDir' => $migrationsDir,
            'config' => self::$generateConfig,
            'migrationsInDb' => true,
        ]);

        $indexes = $this->db->describeIndexes($tableName, $this->defaultSchema);

        $this->assertSame(1, count($indexes));
    }
}
