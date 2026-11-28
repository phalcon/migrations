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

use function array_keys;
use function ob_end_clean;
use function ob_start;
use function sprintf;

/**
 * The foreign keys to a table in another schema, and the composite foreign keys.
 *
 * @see https://github.com/phalcon/migrations/issues/241
 */
final class Issue241Test extends AbstractPostgresqlTestCase
{
    private const REF_SCHEMA = 'issue_241_ref';

    protected function setUp(): void
    {
        parent::setUp();

        $this->getPhalconDb()->execute('DROP SCHEMA IF EXISTS ' . self::REF_SCHEMA . ' CASCADE');
        $this->getPhalconDb()->execute('CREATE SCHEMA ' . self::REF_SCHEMA);
        $this->getPhalconDb()->execute(
            'CREATE TABLE ' . self::REF_SCHEMA . '.parent ('
            . 'id INTEGER NOT NULL PRIMARY KEY, code INTEGER NOT NULL UNIQUE)'
        );
    }

    protected function tearDown(): void
    {
        $this->getPhalconDb()->execute('DROP SCHEMA IF EXISTS ' . self::REF_SCHEMA . ' CASCADE');

        parent::tearDown();
    }

    public function testListReferencesOfCompositeForeignKey(): void
    {
        $db = $this->getPhalconDb();
        $db->execute(
            'CREATE TABLE issue_241_pair (a INTEGER NOT NULL, b INTEGER NOT NULL, PRIMARY KEY (a, b))'
        );
        $db->execute(
            'CREATE TABLE issue_241_pair_child (
                id INTEGER NOT NULL PRIMARY KEY,
                x INTEGER NOT NULL,
                y INTEGER NOT NULL,
                CONSTRAINT fk_issue_241_pair FOREIGN KEY (x, y) REFERENCES issue_241_pair (a, b)
            )'
        );

        $reference = $db->listReferences($this->getDefaultSchema(), 'issue_241_pair_child')['fk_issue_241_pair'];

        $this->assertSame(['x', 'y'], $reference->getColumns());
        $this->assertSame(['a', 'b'], $reference->getReferencedColumns());
        $this->assertSame($this->getDefaultSchema(), $reference->getReferencedSchema());
    }

    public function testListReferencesOfTableInOtherSchema(): void
    {
        $this->createChild('issue_241_list', 'id');

        $references = $this->getPhalconDb()->listReferences($this->getDefaultSchema(), 'issue_241_list');
        $reference  = $references['fk_issue_241_list'];

        $this->assertSame(self::REF_SCHEMA, $reference->getReferencedSchema());
        $this->assertSame('parent', $reference->getReferencedTable());
    }

    public function testRunAddsForeignKeyToTableInOtherSchema(): void
    {
        $table = 'issue_241_add';
        $this->createChild($table, 'id');
        $this->generate($table);

        // The database at another version: no foreign key
        $this->getPhalconDb()->execute(sprintf('ALTER TABLE %s DROP CONSTRAINT fk_%s', $table, $table));

        $this->runMigration($table);

        $references = $this->getPhalconDb()->listReferences($this->getDefaultSchema(), $table);
        $this->assertSame(['fk_' . $table], array_keys($references));
    }

    public function testRunChangesForeignKeyToTableInOtherSchema(): void
    {
        $table = 'issue_241_change';
        $this->createChild($table, 'id');
        $this->generate($table);

        // The database at another version: the foreign key has other columns
        $db = $this->getPhalconDb();
        $db->execute(sprintf('ALTER TABLE %s DROP CONSTRAINT fk_%s', $table, $table));
        $db->execute(
            sprintf(
                'ALTER TABLE %s ADD CONSTRAINT fk_%s FOREIGN KEY (parent_code) REFERENCES %s.parent (code)',
                $table,
                $table,
                self::REF_SCHEMA
            )
        );

        $this->runMigration($table);

        $references = $db->listReferences($this->getDefaultSchema(), $table);
        $this->assertSame(['parent_id'], $references['fk_' . $table]->getColumns());
    }

    private function createChild(string $table, string $column): void
    {
        $this->getPhalconDb()->execute(
            sprintf(
                'CREATE TABLE %1$s (
                    id INTEGER NOT NULL PRIMARY KEY,
                    parent_id INTEGER NOT NULL,
                    parent_code INTEGER NOT NULL,
                    CONSTRAINT fk_%1$s FOREIGN KEY (parent_id) REFERENCES %2$s.parent (%3$s)
                )',
                $table,
                self::REF_SCHEMA,
                $column
            )
        );
    }

    /**
     * Each test uses its own table, because the class name comes from the
     * table name.
     */
    private function generate(string $table): void
    {
        ob_start();
        try {
            Migrations::generate([
                'config'        => static::getMigrationsConfig(),
                'migrationsDir' => $this->getOutputDir($table),
                'tableName'     => $table,
                'version'       => '1.0.0',
            ]);
        } finally {
            ob_end_clean();
        }
    }

    private function runMigration(string $table): void
    {
        ob_start();
        try {
            Migrations::run([
                'config'         => static::getMigrationsConfig(),
                'migrationsDir'  => $this->getOutputPath($table),
                'migrationsInDb' => true,
            ]);
        } finally {
            ob_end_clean();
        }
    }
}
