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

namespace Phalcon\Migrations\Tests\Unit\Mysql;

use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;
use Phalcon\Migrations\Version\ItemCollection;

use function file_get_contents;
use function file_put_contents;
use function ob_get_clean;
use function ob_start;
use function preg_replace;

/**
 * generate makes no new version when no table changed.
 *
 * @see https://github.com/phalcon/migrations/issues/16
 */
final class Issue16Test extends AbstractMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ItemCollection::setType(ItemCollection::TYPE_INCREMENTAL);
    }

    public function testGenerateMakesNoVersionWhenNothingChanged(): void
    {
        $db = $this->getPhalconDb();
        $db->execute(
            'CREATE TABLE `issue_16_same` ('
            . '`id` INT NOT NULL AUTO_INCREMENT, `name` VARCHAR(10) NULL, PRIMARY KEY (`id`))'
        );
        $db->execute("INSERT INTO `issue_16_same` (`name`) VALUES ('a')");
        $dir = $this->getOutputDir(__FUNCTION__);
        $this->generate($dir, ['version' => '1.0.0']);

        // The AUTO_INCREMENT value of another time (the server caches the value), the same structure
        $file = $dir . '/1.0.0/issue_16_same.php';
        $code = (string) file_get_contents($file);
        file_put_contents($file, preg_replace("/'AUTO_INCREMENT' => '[0-9]*'/", "'AUTO_INCREMENT' => '999'", $code));
        $this->assertStringContainsString("'AUTO_INCREMENT' => '999'", (string) file_get_contents($file));

        $output = $this->generate($dir, []);

        $this->assertDirectoryDoesNotExist($dir . '/1.0.1');
        $this->assertStringContainsString('Nothing to generate: no table changed', $output);
    }

    public function testGenerateWithDataAlwaysMakesVersion(): void
    {
        $db = $this->getPhalconDb();
        $db->execute('CREATE TABLE `issue_16_data` (`id` INT NOT NULL, PRIMARY KEY (`id`))');
        $db->execute('INSERT INTO `issue_16_data` (`id`) VALUES (1)');
        $dir = $this->getOutputDir(__FUNCTION__);
        $this->generate($dir, ['version' => '1.0.0', 'exportData' => 'always']);

        $this->generate($dir, ['exportData' => 'always']);

        $this->assertFileExists($dir . '/1.0.1/issue_16_data.php');
        $this->assertFileExists($dir . '/1.0.1/issue_16_data.dat');
    }

    public function testGenerateWritesAllTablesWhenOneChanged(): void
    {
        $db = $this->getPhalconDb();
        $db->execute('CREATE TABLE `issue_16_one` (`id` INT NOT NULL, PRIMARY KEY (`id`))');
        $db->execute('CREATE TABLE `issue_16_two` (`id` INT NOT NULL, PRIMARY KEY (`id`))');
        $dir = $this->getOutputDir(__FUNCTION__);
        $this->generate($dir, ['version' => '1.0.0']);

        $db->execute('ALTER TABLE `issue_16_two` ADD `extra` INT NULL');
        $this->generate($dir, []);

        $this->assertFileExists($dir . '/1.0.1/issue_16_one.php');
        $this->assertFileExists($dir . '/1.0.1/issue_16_two.php');
    }

    /**
     * Generates all the tables, and returns the output
     */
    private function generate(string $dir, array $options): string
    {
        ob_start();
        try {
            Migrations::generate($options + [
                'config'        => static::getMigrationsConfig(),
                'migrationsDir' => [$dir],
                'tableName'     => '@',
            ]);
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }
}
