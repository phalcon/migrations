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
 * @see https://github.com/phalcon/migrations/issues/76
 */
final class Issue76Test extends AbstractPostgresqlTestCase
{
    /**
     * @throws Exception
     */
    public function testNormalRun(): void
    {
        ob_start();
        try {
            Migrations::run([
                'migrationsDir'  => $this->getDataDir('issues/76'),
                'config'         => static::getMigrationsConfig(),
                'migrationsInDb' => true,
            ]);
        } finally {
            ob_end_clean();
        }

        $schema = $this->getDefaultSchema();
        $query  = "SELECT COUNT(*) cnt FROM $schema.user_details WHERE user_id = 62 AND last_name IS NULL";

        $this->assertTrue($this->getPhalconDb()->tableExists('user_details', $schema));
        $this->assertNumRecords(2363, $schema . '.user_details');
        $this->assertEquals(1, $this->getPhalconDb()->fetchOne($query)['cnt']);
    }
}
