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

namespace Phalcon\Migrations\Tests\Integration\MySQL;

use Phalcon\Migrations\Migrations;

use function Phalcon\Migrations\Tests\root_path;

class Issue29Test extends MySQLIntegrationTestCase
{
    public function testIssue29(): void
    {
        Migrations::run([
            'migrationsDir' => root_path('tests/var/issues/29'),
            'config' => self::$generateConfig,
            'migrationsInDb' => true,
        ]);

        $this->assertTrue($this->db->tableExists('tasks'));
        $this->assertTrue($this->db->tableExists('task_jobs'));
        $this->assertArrayHasKey('task_jobs_tasks_id_fk', $this->db->describeReferences('task_jobs'));
    }
}
