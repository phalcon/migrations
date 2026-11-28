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

namespace Phalcon\Migrations\Tests\Unit\Mysql;

use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;

use function array_column;
use function file_get_contents;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function preg_match;
use function preg_match_all;

use const PREG_SET_ORDER;

/**
 * @see https://github.com/phalcon/migrations/issues/139
 * @see https://github.com/phalcon/migrations/issues/218
 */
final class Issue139Test extends AbstractMysqlTestCase
{
    private const TABLE = 'issue_139_test';

    protected function setUp(): void
    {
        parent::setUp();

        $this->getPhalconDb()->execute(
            'CREATE TABLE `' . self::TABLE . '` (
                `c_big` BIGINT UNSIGNED NOT NULL,
                `c_int` INT NOT NULL,
                `c_medium` MEDIUMINT NOT NULL,
                `c_small` SMALLINT NOT NULL,
                `c_tiny` TINYINT NOT NULL,
                `c_bool` TINYINT(1) NOT NULL,
                `c_dec` DECIMAL(10,2) NOT NULL
            )'
        );
    }

    public function testGeneratedIntegerSizeIsTheDisplayWidth(): void
    {
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        try {
            $content = file_get_contents($this->generate($migrationsDir));
        } finally {
            $this->removeDir($migrationsDir);
        }

        $types = array_column(
            $this->getPhalconDb()->fetchAll(
                'SELECT column_name AS n, column_type AS t FROM information_schema.columns
                 WHERE table_schema = :schema AND table_name = :table',
                ['schema' => $_ENV['MYSQL_TEST_DB_DATABASE'], 'table' => self::TABLE]
            ),
            't',
            'n'
        );

        preg_match_all("/new Column\(\s*'(\w+)',\s*\[(.*?)\]\s*\)/s", $content, $columns, PREG_SET_ORDER);
        $this->assertCount(7, $columns);

        foreach ($columns as [, $name, $definition]) {
            $size = preg_match("/'size' => (\d+)/", $definition, $m) === 1 ? (int) $m[1] : null;

            if ($name === 'c_dec') {
                $this->assertSame(10, $size, $name);
                continue;
            }

            if ($name === 'c_bool') {
                $this->assertNull($size, $name);
                continue;
            }

            $width = preg_match('/^[a-z]+\((\d+)\)/', $types[$name], $m) === 1 ? (int) $m[1] : null;
            $this->assertSame($width, $size, $name . ' (' . $types[$name] . ')');
        }
    }

    public function testGeneratedMigrationRunsOnTheSameTableWithNoChange(): void
    {
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        try {
            $this->generate($migrationsDir);

            ob_start();
            try {
                Migrations::run([
                    'migrationsDir'  => $migrationsDir,
                    'config'         => static::getMigrationsConfig(),
                    'migrationsInDb' => true,
                    'verbose'        => true,
                ]);
            } finally {
                $output = (string) ob_get_clean();
            }
        } finally {
            Migrations::resetStorage();
            $this->removeDir($migrationsDir);
        }

        $this->assertStringNotContainsString('CHANGE COLUMN', $output);
    }

    private function generate(string $migrationsDir): string
    {
        ob_start();
        try {
            Migrations::generate([
                'migrationsDir' => $migrationsDir,
                'config'        => static::getMigrationsConfig(),
                'tableName'     => self::TABLE,
            ]);
        } finally {
            ob_end_clean();
        }

        return $migrationsDir . '/1.0.0/' . self::TABLE . '.php';
    }
}
