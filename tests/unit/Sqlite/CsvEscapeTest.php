<?php

/**
 * This file is part of the Phalcon Migrations.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Unit\Sqlite;

use ErrorException;
use Phalcon\Migrations\Db\Adapter\Sqlite;
use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Migration\Action\Generate;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Mvc\Model\Migration;
use Phalcon\Migrations\Tests\AbstractTestCase;
use Phalcon\Migrations\Tests\Fakes\Mvc\Model\MigrationFake;
use Phalcon\Migrations\Utils\Config;
use Phalcon\Migrations\Version\IncrementalItem;
use ReflectionMethod;

/**
 * Covers the CSV calls (fputcsv, fgetcsv, str_getcsv) that must pass the
 * $escape argument explicitly to avoid the PHP 8.4+ deprecation.
 */
final class CsvEscapeTest extends AbstractTestCase
{
    private const TABLE = 'csv_escape_test';

    protected function setUp(): void
    {
        parent::setUp();

        set_error_handler(
            static function (int $errno, string $errstr, string $errfile, int $errline): bool {
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
            },
            E_DEPRECATED | E_USER_DEPRECATED
        );

        Migration::setup(Config::fromArray([
            'database' => [
                'adapter' => 'sqlite',
                'dbname'  => ':memory:',
            ],
        ]));

        Migration::getAdapter()->createTable(self::TABLE, '', [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true]),
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 255, 'notNull' => false]),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        Migrations::resetStorage();
        parent::tearDown();
    }

    public function testBatchInsertReadsDatFileWithoutDeprecation(): void
    {
        $dir = $this->getOutputDir('csv-escape-batch-insert');
        mkdir($dir . '/1.0.0', 0755);
        file_put_contents(
            $dir . '/1.0.0/' . self::TABLE . '.dat',
            "1,Alice\n2,\"Bob, Jr.\"\n3,NULL\n"
        );

        Migration::setMigrationPath($dir . '/');
        $fake = new MigrationFake();
        $fake->setVersion('1.0.0');
        $fake->batchInsert(self::TABLE, ['id', 'name']);

        $this->assertSame(
            [
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob, Jr.'],
                ['id' => 3, 'name' => null],
            ],
            $this->fetchRows()
        );
    }

    public function testBuildColumnParsesEnumOptionsWithoutDeprecation(): void
    {
        $adapter = new Sqlite(Migration::getAdapter()->getConnection());
        $method  = new ReflectionMethod($adapter, 'buildColumn');

        /** @var Column $column */
        $column = $method->invoke(
            $adapter,
            [
                'name'     => 'status',
                'type'     => 'enum',
                'extended' => "enum('draft','published','archived')",
            ],
            true,
            null
        );

        $this->assertSame(['draft', 'published', 'archived'], $column->getOptions());
    }

    public function testCreateDumpFilesWritesDatFileWithoutDeprecation(): void
    {
        $this->insertSampleRows();

        $dir = $this->getOutputDir('csv-escape-dump');
        mkdir($dir . '/1.0.0', 0755);

        $generate = new Generate('sqlite');
        $generate->createDumpFiles(
            self::TABLE,
            $dir . '/',
            Migration::getAdapter(),
            new IncrementalItem('1.0.0'),
            'always'
        );

        $file = $dir . '/1.0.0/' . self::TABLE . '.dat';
        $this->assertFileExists($file);
        $expected = <<<'DAT'
            1,Alice
            2,"Bob, Jr."
            3,"Say \"hi\""
            4,"C:\\path"

            DAT;
        $this->assertSame($expected, file_get_contents($file));
    }

    public function testDumpAndBatchInsertRoundTripPreservesSpecialCharacters(): void
    {
        $expected = $this->insertSampleRows();

        $dir = $this->getOutputDir('csv-escape-round-trip');
        mkdir($dir . '/1.0.0', 0755);

        (new Generate('sqlite'))->createDumpFiles(
            self::TABLE,
            $dir . '/',
            Migration::getAdapter(),
            new IncrementalItem('1.0.0'),
            'always'
        );

        Migration::getAdapter()->execute('DELETE FROM ' . self::TABLE);

        Migration::setMigrationPath($dir . '/');
        $fake = new MigrationFake();
        $fake->setVersion('1.0.0');
        $fake->batchInsert(self::TABLE, ['id', 'name']);

        $this->assertSame($expected, $this->fetchRows());
    }

    private function fetchRows(): array
    {
        $rows = Migration::getAdapter()->fetchAll(
            'SELECT id, name FROM ' . self::TABLE . ' ORDER BY id'
        );

        return array_map(
            static fn(array $row): array => ['id' => (int) $row['id'], 'name' => $row['name']],
            $rows
        );
    }

    private function insertSampleRows(): array
    {
        $rows = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob, Jr.'],
            ['id' => 3, 'name' => 'Say "hi"'],
            ['id' => 4, 'name' => 'C:\\path'],
        ];

        foreach ($rows as $row) {
            Migration::getAdapter()->execute(
                'INSERT INTO ' . self::TABLE . ' (id, name) VALUES (?, ?)',
                [$row['id'], $row['name']]
            );
        }

        return $rows;
    }
}
