<?php
declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Integration;

use Phalcon\Config;
use Phalcon\Migrations\Migrations;
use function Phalcon\Migrations\Tests\root_path;

final class MigrationsTest extends IntegrationTestCase
{
    public function testGenerateEmptyDataBase(): void
    {
        $migrationsDir = root_path('tests/var/output/' . __FUNCTION__);

        $config = new Config([
            'database' => [
                'adapter' => 'mysql',
                'host' => getenv('TEST_DB_HOST'),
                'username' => getenv('TEST_DB_USER'),
                'password' => getenv('TEST_DB_PASSWORD'),
                'dbname' => getenv('TEST_DB_DATABASE'),
            ],
        ]);

        $options = [
            'migrationsDir' => [
                $migrationsDir,
            ],
            'config' => $config,
        ];
        
        Migrations::generate($options);
        
        $this->assertDirectoryExists($migrationsDir);
        $this->assertSame(count(scandir($migrationsDir . '/1.0.0')), 2);
    }
    
    public function testGenerate(): void
    {
        $migrationsDir = root_path('tests/var/output/' . __FUNCTION__);

        $config = new Config([
            'database' => [
                'adapter' => 'mysql',
                'host' => getenv('TEST_DB_HOST'),
                'username' => getenv('TEST_DB_USER'),
                'password' => getenv('TEST_DB_PASSWORD'),
                'dbname' => getenv('TEST_DB_DATABASE'),
            ],
        ]);
        
        $options = [
            'migrationsDir' => [
                $migrationsDir,
            ],
            'config' => $config,
            'tableName' => '@',
        ];
        
        Migrations::generate($options);

        $this->assertDirectoryExists($migrationsDir);
        $this->assertTrue(scandir($migrationsDir . '/1.0.0') > 2);
    }
}
