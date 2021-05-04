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

namespace Phalcon\Migrations\Db\Dialect;

use Phalcon\Db\Dialect\Mysql;
use Phalcon\Db\ReferenceInterface;

class DialectMysql extends Mysql
{
    /**
     * Generates SQL to add an foreign key to a table.
     *
     * @param string $tableName
     * @param string $schemaName
     * @param ReferenceInterface $reference
     * @return string
     */
    public function addForeignKey(string $tableName, string $schemaName, ReferenceInterface $reference): string
    {
        $sql = 'ALTER TABLE ' . $this->prepareTable($tableName, $schemaName) . ' ADD';
        if ($reference->getName()) {
            $sql .= ' CONSTRAINT `' . $reference->getName() . '`';
        }
        $sql .= ' FOREIGN KEY (' . $this->getColumnList($reference->getColumns()) . ') REFERENCES ' .
            $this->prepareTable($reference->getReferencedTable(), $reference->getReferencedSchema()) . '(' .
            $this->getColumnList($reference->getReferencedColumns()) . ')';

        $onDelete = $reference->getOnDelete();
        if ($onDelete) {
            $sql .= ' ON DELETE ' . $onDelete;
        }

        $onUpdate = $reference->getOnUpdate();
        if ($onUpdate) {
            $sql .= ' ON UPDATE ' . $onUpdate;
        }

        return $sql;
    }

    /**
     * Generates SQL to check DB parameter FOREIGN_KEY_CHECKS.
     *
     * @return string
     */
    public function getForeignKeyChecks(): string
    {
        return 'SELECT @@foreign_key_checks';
    }
}
