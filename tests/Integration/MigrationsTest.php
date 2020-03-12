<?php

declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Integration;

use Phalcon\Db\Column;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\Integration\MySQL\MySQLIntegrationTestCase;

use function count;
use function Phalcon\Migrations\Tests\root_path;

final class MigrationsTest extends MySQLIntegrationTestCase
{
    /**
     * @throws \Phalcon\Migrations\Script\ScriptException
     * @throws \Phalcon\Mvc\Model\Exception
     */
    public function testRunAllMigrations(): void
    {
        $this->runIssue66Migrations();
        $migrations = $this->db->fetchAll('SELECT * FROM phalcon_migrations');

        $this->assertEquals(4, count($migrations));
    }

    public function specificMigrationsDataProvider(): array
    {
        return [
            [
                ['0.0.1'],
            ],
            [
                ['0.0.2', '0.0.3'],
            ],
            [
                ['0.0.2', '0.0.3', '0.0.4'],
            ],
            [
                ['0.0.1', '0.0.3', '0.0.4'],
            ],
            [
                ['0.0.1', '0.0.4'],
            ],
            [
                ['0.0.4'],
            ],
        ];
    }

    /**
     * @dataProvider specificMigrationsDataProvider
     *
     * @param array $completedVersion
     * @throws \Phalcon\Migrations\Script\ScriptException
     * @throws \Phalcon\Mvc\Model\Exception
     */
    public function testRunSpecificMigrations(array $completedVersion): void
    {
        $this->insertCompletedMigrations($completedVersion);

        $migrations = $this->db->fetchAll('SELECT * FROM phalcon_migrations');
        $this->assertEquals(count($completedVersion), count($migrations));

        $this->runIssue66Migrations();

        $migrations = $this->db->fetchAll('SELECT * FROM phalcon_migrations');
        $this->assertEquals(4, count($migrations));
    }

    /**
     * @throws \Phalcon\Migrations\Script\ScriptException
     * @throws \Phalcon\Mvc\Model\Exception
     */
    protected function runIssue66Migrations(): void
    {
        Migrations::run([
            'migrationsDir' => root_path('tests/var/issues/66'),
            'config' => self::$generateConfig,
            'migrationsInDb' => true,
        ]);
    }

    /**
     * @param array $versions
     */
    protected function insertCompletedMigrations(array $versions): void
    {
        $this->db->createTable(Migrations::MIGRATION_LOG_TABLE, '', [
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
            $this->db->execute($sql);
        }
    }
}
