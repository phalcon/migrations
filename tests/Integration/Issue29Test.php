<?php
declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Integration;

use Phalcon\Migrations\Migrations;
use function Phalcon\Migrations\Tests\root_path;

class Issue29Test extends IntegrationTestCase
{
    public function testIssue29(): void
    {
        Migrations::run([
            'migrationsDir' => root_path('tests/var/issues/29'),
            'config' => self::$generateConfig,
            'migrationsInDb' => true,
        ]);

        Migrations::resetStorage();

        $this->assertTrue($this->db->tableExists('tasks'));
        $this->assertTrue($this->db->tableExists('task_jobs'));
        $this->assertArrayHasKey('task_jobs_tasks_id_fk', $this->db->describeReferences('task_jobs'));
    }
}
