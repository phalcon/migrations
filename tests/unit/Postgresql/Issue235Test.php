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

use Phalcon\Migrations\Tests\AbstractPostgresqlTestCase;

use function array_keys;

/**
 * @see https://github.com/phalcon/migrations/issues/235
 */
final class Issue235Test extends AbstractPostgresqlTestCase
{
    public function testListColumnsOfColumnsInMoreThanOneKey(): void
    {
        $db = $this->getPhalconDb();
        $db->execute('CREATE TABLE issue_235_parent (id INTEGER NOT NULL PRIMARY KEY, code INTEGER NOT NULL UNIQUE)');
        $db->execute(
            'CREATE TABLE issue_235_child (
                id INTEGER NOT NULL PRIMARY KEY REFERENCES issue_235_parent (id),
                code INTEGER NOT NULL UNIQUE REFERENCES issue_235_parent (code),
                name CHARACTER VARYING(10) NULL
            )'
        );

        $columns = $db->listColumns($this->getDefaultSchema(), 'issue_235_child');

        $this->assertSame(['id', 'code', 'name'], array_keys($columns));
        $this->assertTrue($columns['id']->isPrimary());
        $this->assertTrue($columns['id']->isFirst());
        $this->assertFalse($columns['code']->isPrimary());
        $this->assertSame('id', $columns['code']->getAfterPosition());
        $this->assertSame('code', $columns['name']->getAfterPosition());
    }
}
