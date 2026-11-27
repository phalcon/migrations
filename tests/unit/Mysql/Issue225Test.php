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

use function file_get_contents;
use function ob_end_clean;
use function ob_start;
use function token_get_all;

/**
 * @see https://github.com/phalcon/migrations/issues/225
 */
final class Issue225Test extends AbstractMysqlTestCase
{
    private const TABLE = 'issue_225_test';

    public function testGeneratedFileKeepsTheDefaultAndTheComment(): void
    {
        $db = $this->getPhalconDb();
        $db->execute(
            <<<'SQL'
            CREATE TABLE `issue_225_test` (
                `id` INT NOT NULL,
                `note` VARCHAR(50) NOT NULL DEFAULT 'say "hi" $x \\n' COMMENT 'cost in $USD "net" \\'
            )
            SQL
        );

        $before = $this->describeColumns(self::TABLE)[1];
        $this->assertSame('say "hi" $x \\n', $before->getDefault());
        $this->assertSame('cost in $USD "net" \\', $before->getComment());

        $migrationsDir = $this->getOutputDir(__FUNCTION__);
        ob_start();
        try {
            Migrations::generate([
                'migrationsDir' => $migrationsDir,
                'config'        => static::getMigrationsConfig(),
                'tableName'     => self::TABLE,
            ]);

            // Throws a ParseError when the generated code is not valid PHP
            token_get_all(file_get_contents($migrationsDir . '/1.0.0/' . self::TABLE . '.php'), TOKEN_PARSE);

            $db->dropTable(self::TABLE);
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

        $after = $this->describeColumns(self::TABLE)[1];
        $this->assertSame($before->getDefault(), $after->getDefault());
        $this->assertSame($before->getComment(), $after->getComment());
    }
}
