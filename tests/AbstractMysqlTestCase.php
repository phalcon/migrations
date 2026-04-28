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

namespace Phalcon\Migrations\Tests;

use FilesystemIterator;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Db\Enum;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Utils\Config;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

abstract class AbstractMysqlTestCase extends AbstractTestCase
{
    protected static ?AbstractPdo $phalconDb = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $options = static::getMigrationsConfig()->toArray();
        unset($options['adapter']);

        self::$phalconDb = new PdoMysql($options);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setForeignKeys();
        foreach ($this->getPhalconDb()->listTables() as $table) {
            $this->getPhalconDb()->dropTable($table);
        }
        $this->setForeignKeys(true);
    }

    protected function tearDown(): void
    {
        $this->setForeignKeys();
        foreach ($this->getPhalconDb()->listTables() as $table) {
            $this->getPhalconDb()->dropTable($table);
        }
        $this->setForeignKeys(true);
        Migrations::resetStorage();

        parent::tearDown();
    }

    public function getPhalconDb(): AbstractPdo
    {
        return self::$phalconDb;
    }

    public static function getMigrationsConfig(): Config
    {
        return Config::fromArray([
            'database'    => [
                'adapter'  => 'mysql',
                'host'     => $_ENV['MYSQL_TEST_DB_HOST'],
                'port'     => $_ENV['MYSQL_TEST_DB_PORT'],
                'username' => $_ENV['MYSQL_TEST_DB_USER'],
                'password' => $_ENV['MYSQL_TEST_DB_PASSWORD'],
                'dbname'   => $_ENV['MYSQL_TEST_DB_DATABASE'],
            ],
            'application' => [
                'logInDb' => true,
            ],
        ]);
    }

    protected function assertNumRecords(int $expected, string $table): void
    {
        $result = $this->getPhalconDb()->fetchOne(
            sprintf('SELECT COUNT(*) AS cnt FROM `%s`', $table)
        );
        $this->assertSame($expected, (int) $result['cnt']);
    }

    protected function batchInsert(string $table, array $columns, array $rows): void
    {
        $str = '';
        foreach ($rows as $values) {
            foreach ($values as &$val) {
                if (is_null($val)) {
                    $val = 'NULL';
                    continue;
                }
                if (is_string($val)) {
                    $val = $this->getPhalconDb()->escapeString($val);
                }
            }
            $str .= sprintf('(%s),', implode(',', $values));
        }

        $str   = rtrim($str, ',') . ';';
        $query = sprintf(
            'INSERT INTO `%s` (%s) VALUES %s',
            $table,
            sprintf('`%s`', implode('`,`', $columns)),
            $str
        );

        $this->getPhalconDb()->execute($query);
    }

    protected function getDataDir(string $path = ''): string
    {
        return __DIR__ . '/_data' . ($path ? '/' . ltrim($path, '/') : '');
    }

    protected function getOutputDir(string $path = ''): string
    {
        $dir = __DIR__ . '/_output' . ($path ? '/' . ltrim($path, '/') : '');
        if (is_dir($dir)) {
            $this->removeDir($dir);
        }
        mkdir($dir, 0755, true);

        return $dir;
    }

    protected function grabColumnFromDatabase(string $table, string $column): array
    {
        $results = $this->getPhalconDb()->fetchAll(
            sprintf('SELECT `%s` FROM `%s`', $column, $table),
            Enum::FETCH_ASSOC
        );

        return array_column($results, $column);
    }

    protected function removeDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $directoryIterator = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        $iterator          = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            if ($file->getFileName() === '.gitignore') {
                continue;
            }
            $realPath = $file->getRealPath();
            $file->isDir() ? rmdir($realPath) : unlink($realPath);
        }
        rmdir($path);
    }

    protected function silentRun(string $directory): void
    {
        ob_start();
        try {
            Migrations::run([
                'migrationsDir'  => $this->getDataDir($directory),
                'config'         => static::getMigrationsConfig(),
                'migrationsInDb' => true,
            ]);
        } finally {
            ob_end_clean();
        }
    }

    protected function setForeignKeys(bool $enabled = false): void
    {
        $this->getPhalconDb()->execute('SET FOREIGN_KEY_CHECKS=' . intval($enabled));
    }
}
