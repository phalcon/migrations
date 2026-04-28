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

use Phalcon\Db\Exception;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractPostgresqlTestCase;

/**
 * @see https://github.com/phalcon/migrations/issues/104
 */
final class Issue104Test extends AbstractPostgresqlTestCase
{
    /**
     * @throws Exception
     */
    public function testNormalRun(): void
    {
        $this->getPhalconDb()->execute("SET session_replication_role = 'replica'");

        ob_start();
        try {
            Migrations::run([
                'migrationsDir'  => $this->getDataDir('issues/104'),
                'config'         => static::getMigrationsConfig(),
                'migrationsInDb' => true,
            ]);
        } finally {
            ob_end_clean();
        }

        $this->getPhalconDb()->execute("SET session_replication_role = 'origin'");

        $schema = $this->getDefaultSchema();

        $this->assertTrue($this->getPhalconDb()->tableExists('phalcon_migrations', $schema));
        $this->assertTrue($this->getPhalconDb()->tableExists('foreign_keys_table1', $schema));
        $this->assertTrue($this->getPhalconDb()->tableExists('foreign_keys_table2', $schema));
    }
}
