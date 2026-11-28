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
use function sort;

/**
 * @see https://github.com/phalcon/migrations/issues/221
 */
final class Issue221Test extends AbstractPostgresqlTestCase
{
    private const TABLE = 'issue_221_test';

    public function testRunCreatesTheSecondaryIndexesOfANewTable(): void
    {
        $db = $this->getPhalconDb();
        $db->execute(
            'CREATE TABLE ' . self::TABLE . ' (
                id INTEGER NOT NULL PRIMARY KEY,
                email CHARACTER VARYING(100) NOT NULL,
                name CHARACTER VARYING(100) NOT NULL
            )'
        );
        $db->execute('CREATE UNIQUE INDEX issue_221_email_unique ON ' . self::TABLE . ' (email)');
        $db->execute('CREATE INDEX issue_221_name_idx ON ' . self::TABLE . ' (name)');

        $expected = array_keys($db->listIndexes($this->getDefaultSchema(), self::TABLE));
        sort($expected);

        $migrationsDir = $this->getOutputDir(__FUNCTION__);
        ob_start();
        try {
            Migrations::generate([
                'migrationsDir' => $migrationsDir,
                'config'        => static::getMigrationsConfig(),
                'tableName'     => self::TABLE,
            ]);
            $db->dropTable(self::TABLE, $this->getDefaultSchema());
            Migrations::run([
                'migrationsDir'  => $migrationsDir,
                'config'         => static::getMigrationsConfig(),
                'migrationsInDb' => true,
            ]);
        } finally {
            ob_end_clean();
            Migrations::resetStorage();
            $this->removeDir($migrationsDir);
        }

        $actual = array_keys($db->listIndexes($this->getDefaultSchema(), self::TABLE));
        sort($actual);

        $this->assertSame(['issue_221_email_unique', 'issue_221_name_idx', 'issue_221_test_pkey'], $expected);
        $this->assertSame($expected, $actual);
    }
}
