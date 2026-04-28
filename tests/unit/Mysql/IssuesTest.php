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
use Phalcon\Migrations\Script\ScriptException;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;

final class IssuesTest extends AbstractMysqlTestCase
{
    /**
     * @see https://github.com/phalcon/migrations/issues/2
     *
     * @throws ScriptException
     */
    public function testDisableEnableForeignKeyChecks(): void
    {
        ob_start();
        Migrations::run([
            'migrationsDir'  => $this->getDataDir('issues/2'),
            'config'         => static::getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_end_clean();

        $this->assertTrue($this->getPhalconDb()->tableExists('accessToken'));
        $this->assertTrue($this->getPhalconDb()->tableExists('client'));
        $this->assertArrayHasKey(
            'fk_accessToken_client_1',
            $this->describeReferences('accessToken')
        );
    }

    /**
     * @see https://github.com/phalcon/migrations/issues/29
     *
     * @throws ScriptException
     */
    public function testIssue29(): void
    {
        ob_start();
        Migrations::run([
            'migrationsDir'  => $this->getDataDir('issues/29'),
            'config'         => static::getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_end_clean();

        $this->assertTrue($this->getPhalconDb()->tableExists('tasks'));
        $this->assertTrue($this->getPhalconDb()->tableExists('task_jobs'));
        $this->assertArrayHasKey(
            'task_jobs_tasks_id_fk',
            $this->describeReferences('task_jobs')
        );
    }
}