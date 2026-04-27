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

namespace Phalcon\Migrations\Db\Adapter;

use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Db\Connection;
use Phalcon\Migrations\Db\Index;
use Phalcon\Migrations\Db\Reference;

use function implode;
use function in_array;
use function str_getcsv;
use function stripos;
use function strtolower;
use function substr;
use function trim;

abstract class AbstractAdapter implements AdapterInterface
{
    protected string $currentSchemaSql = '';

    public function __construct(
        protected readonly Connection $connection
    ) {
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function begin(): void
    {
        $this->connection->begin();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollback();
    }

    public function execute(string $sql, array $values = []): void
    {
        $this->connection->execute($sql, $values);
    }

    public function fetchAll(string $sql, array $values = []): array
    {
        return $this->connection->fetchAll($sql, $values);
    }

    public function fetchOne(string $sql, array $values = []): array
    {
        return $this->connection->fetchOne($sql, $values);
    }

    public function quote(string $value): string
    {
        return $this->connection->quote($value);
    }

    public function getCurrentSchema(): string
    {
        return (string) $this->connection->fetchValue($this->currentSchemaSql);
    }

    public function tableExists(string $table, string $schema = ''): bool
    {
        $schema = $schema ?: $this->getCurrentSchema();

        return (bool) $this->connection->fetchValue(
            $this->getTableExistsSql(),
            ['schema' => $schema, 'table' => $table]
        );
    }

    public function listColumns(string $schema, string $table): array
    {
        $rows    = $this->fetchColumnRows($schema, $table);
        $columns = [];
        $prev    = null;
        $isFirst = true;

        foreach ($rows as $row) {
            $col               = $this->buildColumn($row, $isFirst, $prev);
            $columns[$col->getName()] = $col;
            $prev              = $col->getName();
            $isFirst           = false;
        }

        return $this->processColumnInformation($schema, $table, $columns);
    }

    public function createTable(string $table, string $schema, array $definition): void
    {
        $sql = $this->buildCreateTableSql($table, $schema, $definition);
        $this->connection->execute($sql);
    }

    public function addColumn(string $table, string $schema, Column $column): void
    {
        $this->connection->execute($this->buildAddColumnSql($table, $schema, $column));
    }

    public function dropColumn(string $table, string $schema, string $column): void
    {
        $t = $this->qualifyTable($table, $schema);
        $c = $this->connection->quoteIdentifier($column);
        $this->connection->execute("ALTER TABLE {$t} DROP COLUMN {$c}");
    }

    public function addIndex(string $table, string $schema, Index $index): void
    {
        $this->connection->execute($this->buildAddIndexSql($table, $schema, $index));
    }

    public function addPrimaryKey(string $table, string $schema, Index $index): void
    {
        $this->connection->execute($this->buildAddPrimaryKeySql($table, $schema, $index));
    }

    public function addForeignKey(string $table, string $schema, Reference $reference): void
    {
        $this->connection->execute($this->buildAddForeignKeySql($table, $schema, $reference));
    }

    public function dropForeignKey(string $table, string $schema, string $name): void
    {
        $this->connection->execute($this->buildDropForeignKeySql($table, $schema, $name));
    }

    // -------------------------------------------------------------------------
    // Helpers used by both introspection and DDL
    // -------------------------------------------------------------------------

    protected function isPrimaryIndex(Index $index): bool
    {
        return $index->getType() === Index::TYPE_PRIMARY
            || $index->getType() === Index::TYPE_PRIMARY_ALT
            || $index->getName() === 'PRIMARY';
    }

    protected function qualifyTable(string $table, string $schema): string
    {
        $t = $this->connection->quoteIdentifier($table);
        if ($schema !== '') {
            return $this->connection->quoteIdentifier($schema) . '.' . $t;
        }

        return $t;
    }

    protected function quoteColumns(array $columns): string
    {
        $quoted = [];
        foreach ($columns as $col) {
            $quoted[] = $this->connection->quoteIdentifier($col);
        }

        return implode(', ', $quoted);
    }

    // -------------------------------------------------------------------------
    // Column introspection — shared SQL (information_schema based)
    // -------------------------------------------------------------------------

    protected function getListColumnSql(): string
    {
        $autoInc  = $this->getAutoIncSql();
        $comment  = $this->getCommentSql();
        $extended = $this->getExtendedSql();
        $unsigned = $this->getUnsignedSql();

        return "
            SELECT
                c.column_name    AS name,
                c.data_type      AS type,
                COALESCE(
                    c.character_maximum_length,
                    c.numeric_precision
                )                AS size,
                c.numeric_scale  AS numeric_scale,
                CASE
                    WHEN (
                        c.column_default IS NULL
                        OR c.column_default = 'NULL'
                        OR POSITION('CURRENT_TIMESTAMP' IN c.column_default) > 0
                    ) THEN NULL
                    ELSE c.column_default
                END              AS default_value,
                CASE c.data_type
                    WHEN 'bigint'    THEN 1
                    WHEN 'decimal'   THEN 1
                    WHEN 'double'    THEN 1
                    WHEN 'float'     THEN 1
                    WHEN 'int'       THEN 1
                    WHEN 'integer'   THEN 1
                    WHEN 'mediumint' THEN 1
                    WHEN 'numeric'   THEN 1
                    WHEN 'real'      THEN 1
                    WHEN 'smallint'  THEN 1
                    WHEN 'tinyint'   THEN 1
                    ELSE 0
                END              AS is_numeric,
                CASE c.is_nullable
                    WHEN 'YES' THEN 0
                    ELSE 1
                END              AS is_not_null,
                $comment         AS comment,
                CASE c.ordinal_position WHEN 1 THEN 1 ELSE 0 END AS is_first,
                CASE tc.constraint_type
                    WHEN 'PRIMARY KEY' THEN 1
                    ELSE 0
                END              AS is_primary,
                $unsigned        AS is_unsigned,
                $autoInc         AS is_auto_increment,
                $extended        AS extended
            FROM information_schema.columns c
                LEFT JOIN information_schema.key_column_usage kcu
                    ON  c.table_schema = kcu.table_schema
                    AND c.table_name   = kcu.table_name
                    AND c.column_name  = kcu.column_name
                LEFT JOIN information_schema.table_constraints tc
                    ON  kcu.table_schema    = tc.table_schema
                    AND kcu.table_name      = tc.table_name
                    AND kcu.constraint_name = tc.constraint_name
                    AND tc.constraint_type  = 'PRIMARY KEY'
            WHERE c.table_schema = :schema
            AND   c.table_name   = :table
            ORDER BY c.ordinal_position
        ";
    }

    protected function fetchColumnRows(string $schema, string $table): array
    {
        return $this->connection->fetchAll(
            $this->getListColumnSql(),
            ['schema' => $schema, 'table' => $table]
        );
    }

    protected function buildColumn(array $row, bool $isFirst, ?string $previous): Column
    {
        $type    = $this->mapType($row['type'], (string) ($row['extended'] ?? ''));
        $default = $this->processDefault($row['default_value'] ?? null, $row['type']);

        $definition = [
            'type'          => $type,
            'size'          => isset($row['size'])          ? (int) $row['size']          : null,
            'scale'         => isset($row['numeric_scale']) ? (int) $row['numeric_scale'] : null,
            'notNull'       => (bool) ($row['is_not_null']       ?? false),
            'unsigned'      => (bool) ($row['is_unsigned']       ?? false),
            'autoIncrement' => (bool) ($row['is_auto_increment'] ?? false),
            'primary'       => (bool) ($row['is_primary']        ?? false),
            'first'         => $isFirst,
            'after'         => $previous,
            'comment'       => (string) ($row['comment'] ?? ''),
        ];

        if ($default !== null) {
            $definition['default'] = $default;
        }

        $extended = trim((string) ($row['extended'] ?? ''));
        if (stripos($extended, 'enum') === 0) {
            $input                = trim(substr($extended, 4), '()');
            $definition['options'] = array_map(
                static fn(string $v) => trim($v, "'"),
                str_getcsv($input)
            );
        }

        return new Column((string) $row['name'], $definition);
    }

    protected function processDefault(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        $type       = strtolower($type);
        $intTypes   = ['bigint', 'int', 'integer', 'mediumint', 'smallint', 'tinyint'];
        $floatTypes = ['decimal', 'double', 'float', 'numeric', 'real'];

        return match (true) {
            in_array($type, $intTypes, true)   => (int) $value,
            in_array($type, $floatTypes, true) => (float) $value,
            default                            => $value,
        };
    }

    /** @param Column[] $columns */
    protected function processColumnInformation(string $schema, string $table, array $columns): array
    {
        return $columns;
    }

    // -------------------------------------------------------------------------
    // Hooks for driver-specific SQL fragments
    // -------------------------------------------------------------------------

    protected function getAutoIncSql(): string
    {
        return "''";
    }

    protected function getCommentSql(): string
    {
        return "''";
    }

    protected function getExtendedSql(): string
    {
        return "''";
    }

    protected function getUnsignedSql(): string
    {
        return 'NULL';
    }

    protected function getTableExistsSql(): string
    {
        return "
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = :schema
            AND   table_name   = :table
        ";
    }

    abstract protected function mapType(string $infoType, string $extended): string;

    // -------------------------------------------------------------------------
    // DDL helpers (drivers override as needed)
    // -------------------------------------------------------------------------

    abstract protected function buildColumnDefinitionSql(Column $column): string;

    protected function buildAddColumnSql(string $table, string $schema, Column $column): string
    {
        $t   = $this->qualifyTable($table, $schema);
        $def = $this->buildColumnDefinitionSql($column);
        $sql = "ALTER TABLE {$t} ADD COLUMN {$def}";

        if ($column->isFirst()) {
            $sql .= ' FIRST';
        } elseif ($column->getAfterPosition() !== null) {
            $sql .= ' AFTER ' . $this->connection->quoteIdentifier($column->getAfterPosition());
        }

        return $sql;
    }

    protected function buildAddIndexSql(string $table, string $schema, Index $index): string
    {
        $t    = $this->qualifyTable($table, $schema);
        $name = $this->connection->quoteIdentifier($index->getName());
        $cols = $this->quoteColumns($index->getColumns());

        $unique = strtolower($index->getType()) === 'unique' ? 'UNIQUE ' : '';

        return "ALTER TABLE {$t} ADD {$unique}INDEX {$name} ({$cols})";
    }

    protected function buildAddPrimaryKeySql(string $table, string $schema, Index $index): string
    {
        $t    = $this->qualifyTable($table, $schema);
        $cols = $this->quoteColumns($index->getColumns());

        return "ALTER TABLE {$t} ADD PRIMARY KEY ({$cols})";
    }

    protected function buildAddForeignKeySql(string $table, string $schema, Reference $reference): string
    {
        $t    = $this->qualifyTable($table, $schema);
        $cols = $this->quoteColumns($reference->getColumns());
        $rt   = $this->qualifyTable(
            $reference->getReferencedTable(),
            $reference->getReferencedSchema() ?? ''
        );
        $rcols = $this->quoteColumns($reference->getReferencedColumns());

        $sql = "ALTER TABLE {$t} ADD";
        if ($reference->getName() !== '') {
            $sql .= ' CONSTRAINT ' . $this->connection->quoteIdentifier($reference->getName());
        }

        $sql .= " FOREIGN KEY ({$cols}) REFERENCES {$rt} ({$rcols})";

        if ($reference->getOnDelete() !== '') {
            $sql .= ' ON DELETE ' . $reference->getOnDelete();
        }
        if ($reference->getOnUpdate() !== '') {
            $sql .= ' ON UPDATE ' . $reference->getOnUpdate();
        }

        return $sql;
    }

    protected function buildDropForeignKeySql(string $table, string $schema, string $name): string
    {
        $t    = $this->qualifyTable($table, $schema);
        $cname = $this->connection->quoteIdentifier($name);

        return "ALTER TABLE {$t} DROP FOREIGN KEY {$cname}";
    }

    protected function buildCreateTableSql(string $table, string $schema, array $definition): string
    {
        $t        = $this->qualifyTable($table, $schema);
        $parts    = [];

        foreach ($definition['columns'] ?? [] as $column) {
            $parts[] = '    ' . $this->buildColumnDefinitionSql($column);
        }

        foreach ($definition['indexes'] ?? [] as $index) {
            if ($this->isPrimaryIndex($index)) {
                $cols    = $this->quoteColumns($index->getColumns());
                $parts[] = "    PRIMARY KEY ({$cols})";
            } elseif ($index->getType() === Index::TYPE_UNIQUE) {
                $name    = $this->connection->quoteIdentifier($index->getName());
                $cols    = $this->quoteColumns($index->getColumns());
                $parts[] = "    UNIQUE KEY {$name} ({$cols})";
            } else {
                $name    = $this->connection->quoteIdentifier($index->getName());
                $cols    = $this->quoteColumns($index->getColumns());
                $parts[] = "    KEY {$name} ({$cols})";
            }
        }

        foreach ($definition['references'] ?? [] as $ref) {
            $cols  = $this->quoteColumns($ref->getColumns());
            $rt    = $this->qualifyTable($ref->getReferencedTable(), $ref->getReferencedSchema() ?? '');
            $rcols = $this->quoteColumns($ref->getReferencedColumns());
            $cname = $ref->getName() !== ''
                ? 'CONSTRAINT ' . $this->connection->quoteIdentifier($ref->getName()) . ' '
                : '';

            $fk = "    {$cname}FOREIGN KEY ({$cols}) REFERENCES {$rt} ({$rcols})";
            if ($ref->getOnDelete() !== '') {
                $fk .= ' ON DELETE ' . $ref->getOnDelete();
            }
            if ($ref->getOnUpdate() !== '') {
                $fk .= ' ON UPDATE ' . $ref->getOnUpdate();
            }

            $parts[] = $fk;
        }

        $sql = "CREATE TABLE {$t} (\n" . implode(",\n", $parts) . "\n)";

        $options = $definition['options'] ?? [];
        $sql    .= $this->buildTableOptionsSql($options);

        return $sql;
    }

    protected function buildTableOptionsSql(array $options): string
    {
        return '';
    }
}
