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

namespace Phalcon\Migrations\Tests\Unit\Postgresql;

use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractPostgresqlTestCase;

use function file_get_contents;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function preg_match_all;

use const PREG_SET_ORDER;

/**
 * @see https://github.com/phalcon/migrations/issues/105
 */
final class Issue105Test extends AbstractPostgresqlTestCase
{
    private const TABLE = 'issue_105_test';
    private const UUID  = '0a3f1c2e-5b4d-4e6f-8a9b-0c1d2e3f4a5b';

    protected function setUp(): void
    {
        parent::setUp();

        $this->getPhalconDb()->execute(
            'CREATE TABLE ' . self::TABLE . ' (id UUID NOT NULL PRIMARY KEY, ref UUID NULL)'
        );
        $this->getPhalconDb()->execute(
            'INSERT INTO ' . self::TABLE . ' (id, ref) VALUES (:id, :id)',
            ['id' => self::UUID]
        );
    }

    public function testGeneratedFileKeepsTheUuidType(): void
    {
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        try {
            $content = file_get_contents($this->generate($migrationsDir));
        } finally {
            $this->removeDir($migrationsDir);
        }

        preg_match_all("/new Column\(\s*'(\w+)',\s*\[(.*?)\]\s*\)/s", $content, $columns, PREG_SET_ORDER);
        $this->assertCount(2, $columns);

        foreach ($columns as [, $name, $definition]) {
            $this->assertStringContainsString("'type' => Column::TYPE_UUID", $definition, $name);
            $this->assertStringNotContainsString("'size'", $definition, $name);
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

        $this->assertStringNotContainsString('ALTER COLUMN', $output);

        $row = $this->getPhalconDb()->fetchOne('SELECT id, ref FROM ' . self::TABLE);
        $this->assertSame(self::UUID, $row['id']);
        $this->assertSame(self::UUID, $row['ref']);
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
