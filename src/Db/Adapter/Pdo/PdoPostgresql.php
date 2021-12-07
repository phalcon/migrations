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

namespace Phalcon\Migrations\Db\Adapter\Pdo;

use Phalcon\Db\Adapter\Pdo\Postgresql;
use Phalcon\Db\Enum;
use Phalcon\Db\Index;
use Phalcon\Db\IndexInterface;
use Phalcon\Db\Reference;
use Phalcon\Db\ReferenceInterface;

class PdoPostgresql extends Postgresql
{
    public const INDEX_TYPE_PRIMARY = 'PRIMARY KEY';
    public const INDEX_TYPE_UNIQUE = 'UNIQUE';

    /**
     * Lists table references
     *
     * @param string $table
     * @param string|null $schema
     *
     * @return ReferenceInterface[]
     */
    public function describeReferences(string $table, string $schema = null): array
    {
        $references = [];

        $rows = $this->fetchAll($this->getDialect()->describeReferences($table, $schema), Enum::FETCH_NUM);
        foreach ($rows as $reference) {
            $constraintName = $reference[2];
            if (!isset($references[$constraintName])) {
                $referencedSchema  = $reference[3];
                $referencedTable   = $reference[4];
                $referenceUpdate   = $reference[6];
                $referenceDelete   = $reference[7];
                $columns           = [];
                $referencedColumns = [];
            } else {
                $referencedSchema  = $references[$constraintName]['referencedSchema'];
                $referencedTable   = $references[$constraintName]['referencedTable'];
                $columns           = $references[$constraintName]['columns'];
                $referencedColumns = $references[$constraintName]['referencedColumns'];
                $referenceUpdate   = $references[$constraintName]['onUpdate'];
                $referenceDelete   = $references[$constraintName]['onDelete'];
            }

            $columns[] = $reference[1];
            $referencedColumns[] = $reference[5];

            $references[$constraintName] = [
                'referencedSchema'  => $referencedSchema,
                'referencedTable'   => $referencedTable,
                'columns'           => $columns,
                'referencedColumns' => $referencedColumns,
                'onUpdate'          => $referenceUpdate,
                'onDelete'          => $referenceDelete
            ];
        }

        $referenceObjects = [];
        foreach ($references as $name => $arrayReference) {
            $referenceObjects[$name] = new Reference($name, [
                'referencedSchema'  => $arrayReference['referencedSchema'],
                'referencedTable'   => $arrayReference['referencedTable'],
                'columns'           => $arrayReference['columns'],
                'referencedColumns' => $arrayReference['referencedColumns'],
                'onUpdate'          => $arrayReference['onUpdate'],
                'onDelete'          => $arrayReference['onDelete'],
            ]);
        }

        return $referenceObjects;
    }

    /**
     * @param string $table
     * @param string|null $schema
     *
     * @return IndexInterface[]
     */
    public function describeIndexes(string $table, string $schema = null): array
    {
        $indexes = [];
        $indexObjects = [];

        $_indexes = $this->fetchAll($this->dialect->describeIndexes($table, $schema));
        foreach ($_indexes as $index) {
            $keyName = $index['key_name'] ?? $index[2];
            $nonUnique = $index['non_unique'] ?? true;
            $isPrimary = $index['is_primary'] ?? false;

            if ($isPrimary) {
                $indexType = self::INDEX_TYPE_PRIMARY;
            } elseif (!$nonUnique) {
                $indexType = self::INDEX_TYPE_UNIQUE;
            } else {
                $indexType = '';
            }

            $columns = $indexes[$keyName]['columns'] ?? [];
            $columns[] = $index['column_name'] ?? $index[4];
            $indexes[$keyName]['columns'] = $columns;
            $indexes[$keyName]['type'] = $indexType;
        }

        foreach ($indexes as $name => $index) {
            $indexObjects[$name] = new Index(
                $name,
                $index['columns'],
                $index['type']
            );
        }

        return $indexObjects;
    }
}
