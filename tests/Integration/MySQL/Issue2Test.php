<?php
declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Integration\MySQL;

use Phalcon\Migrations\Migrations;
use function Phalcon\Migrations\Tests\root_path;

class Issue2Test extends MySQLIntegrationTestCase
{
    public function testDisableEnableForeignKeyChecks(): void
    {
        Migrations::run([
            'migrationsDir' => root_path('tests/var/issues/2'),
            'config' => self::$generateConfig,
            'migrationsInDb' => true,
        ]);

        $this->assertTrue($this->db->tableExists('accessToken'));
        $this->assertTrue($this->db->tableExists('client'));
        $this->assertArrayHasKey('fk_accessToken_client_1', $this->db->describeReferences('accessToken'));
    }
}
