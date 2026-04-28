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
use Phalcon\Migrations\Db\Index;
use Phalcon\Migrations\Db\Reference;

use function explode;
use function is_string;
use function sprintf;
use function strtolower;
use function strtoupper;
use function trim;

class Postgresql extends AbstractAdapter
{
    protected string $currentSchemaSql = 'SELECT CURRENT_SCHEMA';

    private const TYPE_MAP = [
        'bigint'                    => Column::TYPE_BIGINTEGER,
        'bit'                       => Column::TYPE_BIT,
        'boolean'                   => Column::TYPE_BOOLEAN,
        'bytea'                     => Column::TYPE_BLOB,
        'character'                 => Column::TYPE_CHAR,
        'character varying'         => Column::TYPE_VARCHAR,
        'date'                      => Column::TYPE_DATE,
        'double precision'          => Column::TYPE_DOUBLE,
        'float'                     => Column::TYPE_FLOAT,
        'integer'                   => Column::TYPE_INTEGER,
        'json'                      => Column::TYPE_JSON,
        'jsonb'                     => Column::TYPE_JSONB,
        'numeric'                   => Column::TYPE_DECIMAL,
        'real'                      => Column::TYPE_FLOAT,
        'smallint'                  => Column::TYPE_SMALLINTEGER,
        'text'                      => Column::TYPE_TEXT,
        'time'                      => Column::TYPE_TIME,
        'time without time zone'    => Column::TYPE_TIME,
        'timestamp'                 => Column::TYPE_TIMESTAMP,
        'timestamp without time zone' => Column::TYPE_TIMESTAMP,
        'timestamp with time zone'  => Column::TYPE_TIMESTAMP,
    ];

    private const DDL_TYPE_MAP = [
        Column::TYPE_BIGINTEGER   => 'BIGINT',
        Column::TYPE_BIT          => 'BIT',
        Column::TYPE_BLOB         => 'BYTEA',
        Column::TYPE_BOOLEAN      => 'BOOLEAN',
        Column::TYPE_CHAR         => 'CHARACTER',
        Column::TYPE_DATE         => 'DATE',
        Column::TYPE_DATETIME     => 'TIMESTAMP',
        Column::TYPE_DECIMAL      => 'NUMERIC',
        Column::TYPE_DOUBLE       => 'DOUBLE PRECISION',
        Column::TYPE_ENUM         => 'VARCHAR',
        Column::TYPE_FLOAT        => 'FLOAT',
        Column::TYPE_INTEGER      => 'INTEGER',
        Column::TYPE_JSON         => 'JSON',
        Column::TYPE_JSONB        => 'JSONB',
        Column::TYPE_LONGBLOB     => 'BYTEA',
        Column::TYPE_LONGTEXT     => 'TEXT',
        Column::TYPE_MEDIUMBLOB   => 'BYTEA',
        Column::TYPE_MEDIUMINTEGER => 'INTEGER',
        Column::TYPE_MEDIUMTEXT   => 'TEXT',
        Column::TYPE_SMALLINTEGER => 'SMALLINT',
        Column::TYPE_TEXT         => 'TEXT',
        Column::TYPE_TIME         => 'TIME',
        Column::TYPE_TIMESTAMP    => 'TIMESTAMP',
        Column::TYPE_TINYBLOB     => 'BYTEA',
        Column::TYPE_TINYINTEGER  => 'SMALLINT',
        Column::TYPE_TINYTEXT     => 'TEXT',
        Column::TYPE_VARCHAR      => 'CHARACTER VARYING',
    ];

    private const NO_SIZE_TYPES = [
        Column::TYPE_BIGINTEGER,
        Column::TYPE_BLOB,
        Column::TYPE_BOOLEAN,
        Column::TYPE_DATE,
        Column::TYPE_DATETIME,
        Column::TYPE_DOUBLE,
        Column::TYPE_FLOAT,
        Column::TYPE_INTEGER,
        Column::TYPE_JSON,
        Column::TYPE_JSONB,
        Column::TYPE_LONGBLOB,
        Column::TYPE_LONGTEXT,
        Column::TYPE_MEDIUMBLOB,
        Column::TYPE_MEDIUMINTEGER,
        Column::TYPE_MEDIUMTEXT,
        Column::TYPE_SMALLINTEGER,
        Column::TYPE_TEXT,
        Column::TYPE_TIME,
        Column::TYPE_TIMESTAMP,
        Column::TYPE_TINYBLOB,
        Column::TYPE_TINYINTEGER,
        Column::TYPE_TINYTEXT,
    ];

    public function listTables(string $schema): array
    {
        return $this->connection->fetchColumn(
            "SELECT table_name
             FROM   information_schema.tables
             WHERE  table_schema = :schema
             AND    UPPER(table_type) = 'BASE TABLE'
             ORDER BY table_name",
            ['schema' => $schema]
        );
    }

    public function listIndexes(string $schema, string $table): array
    {
        $rows = $this->connection->fetchAll(
            "SELECT
                 i.relname              AS key_name,
                 NOT ix.indisunique     AS non_unique,
                 ix.indisprimary        AS is_primary,
                 a.attname              AS column_name
             FROM pg_class t
             JOIN pg_index ix  ON t.oid = ix.indrelid
             JOIN pg_class i   ON i.oid = ix.indexrelid
             JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(ix.indkey)
             JOIN pg_namespace n ON n.oid = t.relnamespace
             WHERE t.relkind   = 'r'
             AND   t.relname   = :table
             AND   n.nspname   = :schema
             ORDER BY i.relname",
            ['schema' => $schema, 'table' => $table]
        );

        $groups = [];
        foreach ($rows as $row) {
            $name = $row['key_name'];
            $groups[$name]['columns'][]   = $row['column_name'];
            $groups[$name]['non_unique']  = (bool) $row['non_unique'];
            $groups[$name]['is_primary']  = (bool) $row['is_primary'];
        }

        $indexes = [];
        foreach ($groups as $name => $data) {
            if ($data['is_primary']) {
                $type = Index::TYPE_PRIMARY;
            } elseif (!$data['non_unique']) {
                $type = Index::TYPE_UNIQUE;
            } else {
                $type = '';
            }

            $indexes[$name] = new Index($name, $data['columns'], $type);
        }

        return $indexes;
    }

    public function listReferences(string $schema, string $table): array
    {
        $rows = $this->connection->fetchAll(
            "SELECT DISTINCT
                 tc.constraint_name,
                 kcu.column_name,
                 tc.table_schema        AS referenced_schema,
                 ccu.table_name         AS referenced_table,
                 ccu.column_name        AS referenced_column,
                 rc.update_rule,
                 rc.delete_rule
             FROM information_schema.table_constraints tc
             JOIN information_schema.key_column_usage kcu
                  ON tc.constraint_name = kcu.constraint_name
                  AND tc.table_schema   = kcu.table_schema
             JOIN information_schema.constraint_column_usage ccu
                  ON ccu.constraint_name = tc.constraint_name
             JOIN information_schema.referential_constraints rc
                  ON tc.constraint_catalog = rc.constraint_catalog
                  AND tc.constraint_schema  = rc.constraint_schema
                  AND tc.constraint_name    = rc.constraint_name
             WHERE tc.constraint_type = 'FOREIGN KEY'
             AND   tc.table_schema    = :schema
             AND   tc.table_name      = :table
             ORDER BY tc.constraint_name",
            ['schema' => $schema, 'table' => $table]
        );

        $groups = [];
        foreach ($rows as $row) {
            $name = $row['constraint_name'];
            if (!isset($groups[$name])) {
                $groups[$name] = [
                    'referencedSchema'  => $row['referenced_schema'],
                    'referencedTable'   => $row['referenced_table'],
                    'onUpdate'          => $row['update_rule'],
                    'onDelete'          => $row['delete_rule'],
                    'columns'           => [],
                    'referencedColumns' => [],
                ];
            }

            $groups[$name]['columns'][]           = $row['column_name'];
            $groups[$name]['referencedColumns'][] = $row['referenced_column'];
        }

        $references = [];
        foreach ($groups as $name => $data) {
            $references[$name] = new Reference($name, $data);
        }

        return $references;
    }

    public function getTableOptions(string $schema, string $table): array
    {
        return [];
    }

    public function modifyColumn(string $table, string $schema, Column $new, Column $current): void
    {
        $t       = $this->qualifyTable($table, $schema);
        $newName = $this->connection->quoteIdentifier($new->getName());
        $oldName = $this->connection->quoteIdentifier($current->getName());
        $ddlType = self::DDL_TYPE_MAP[$new->getType()] ?? strtoupper($new->getType());

        if (!in_array($new->getType(), self::NO_SIZE_TYPES, true) && $new->getSize() !== null) {
            $ddlType .= sprintf('(%s', $new->getSize());
            if ($new->getScale() !== null) {
                $ddlType .= sprintf(',%d', $new->getScale());
            }

            $ddlType .= ')';
        }

        $this->connection->execute(
            "ALTER TABLE {$t} ALTER COLUMN {$oldName} TYPE {$ddlType}"
        );

        if ($new->isNotNull() !== $current->isNotNull()) {
            $constraint = $new->isNotNull() ? 'SET NOT NULL' : 'DROP NOT NULL';
            $this->connection->execute("ALTER TABLE {$t} ALTER COLUMN {$newName} {$constraint}");
        }

        if ($new->hasDefault()) {
            $default = $new->getDefault();
            if ($default === null) {
                $this->connection->execute("ALTER TABLE {$t} ALTER COLUMN {$newName} DROP DEFAULT");
            } else {
                $this->connection->execute(
                    "ALTER TABLE {$t} ALTER COLUMN {$newName} SET DEFAULT "
                    . $this->connection->quote((string) $default)
                );
            }
        }

        if ($new->getName() !== $current->getName()) {
            $this->connection->execute(
                "ALTER TABLE {$t} RENAME COLUMN {$oldName} TO {$newName}"
            );
        }
    }

    public function dropIndex(string $table, string $schema, string $name): void
    {
        $n = $this->connection->quoteIdentifier(
            $schema !== '' ? $schema . '.' . $name : $name
        );
        $this->connection->execute("DROP INDEX IF EXISTS {$n}");
    }

    public function dropPrimaryKey(string $table, string $schema): void
    {
        $constraintName = $table . '_pkey';
        $t = $this->qualifyTable($table, $schema);
        $n = $this->connection->quoteIdentifier($constraintName);
        $this->connection->execute("ALTER TABLE {$t} DROP CONSTRAINT IF EXISTS {$n}");
    }

    // -------------------------------------------------------------------------
    // DDL overrides — PostgreSQL uses CREATE INDEX instead of ALTER TABLE ADD INDEX
    // -------------------------------------------------------------------------

    protected function buildAddIndexSql(string $table, string $schema, Index $index): string
    {
        $name   = $this->connection->quoteIdentifier($index->getName());
        $t      = $this->qualifyTable($table, $schema);
        $cols   = $this->quoteColumns($index->getColumns());
        $unique = strtolower($index->getType()) === 'unique' ? 'UNIQUE ' : '';

        return "CREATE {$unique}INDEX {$name} ON {$t} ({$cols})";
    }

    protected function buildDropForeignKeySql(string $table, string $schema, string $name): string
    {
        $t = $this->qualifyTable($table, $schema);
        $n = $this->connection->quoteIdentifier($name);

        return "ALTER TABLE {$t} DROP CONSTRAINT IF EXISTS {$n}";
    }

    protected function buildAddColumnSql(string $table, string $schema, Column $column): string
    {
        $t   = $this->qualifyTable($table, $schema);
        $def = $this->buildColumnDefinitionSql($column);

        return "ALTER TABLE {$t} ADD COLUMN {$def}";
    }

    // -------------------------------------------------------------------------
    // Driver-specific SQL fragments
    // -------------------------------------------------------------------------

    protected function getAutoIncSql(): string
    {
        return "CASE SUBSTRING(c.column_default FROM 1 FOR 7)"
            . " WHEN 'nextval' THEN 1 ELSE 0 END";
    }

    protected function mapType(string $infoType, string $extended): string
    {
        $base = strtolower(trim($infoType));

        return self::TYPE_MAP[$base] ?? Column::TYPE_VARCHAR;
    }

    protected function processDefault(mixed $value, string $type): mixed
    {
        if (is_string($value)) {
            $parts = explode('::', $value);
            if (count($parts) === 2) {
                $value = trim($parts[0], "'");
            }
        }

        return parent::processDefault($value, $type);
    }

    /** @param Column[] $columns */
    protected function processColumnInformation(string $schema, string $table, array $columns): array
    {
        $comments = $this->connection->fetchPairs(
            "SELECT i.column_name, d.description
             FROM pg_catalog.pg_statio_all_tables s
             JOIN pg_catalog.pg_description d       ON d.objoid     = s.relid
             JOIN information_schema.columns i       ON d.objsubid  = i.ordinal_position
                                                    AND i.table_schema = s.schemaname
                                                    AND i.table_name   = s.relname
             WHERE i.table_schema = :schema
             AND   i.table_name   = :table",
            ['schema' => $schema, 'table' => $table]
        );

        foreach ($comments as $colName => $comment) {
            if (isset($columns[$colName])) {
                $old        = $columns[$colName];
                $definition = $this->columnToDefinition($old);
                $definition['comment'] = $comment;
                $columns[$colName]     = new Column($colName, $definition);
            }
        }

        return $columns;
    }

    protected function buildColumnDefinitionSql(Column $column): string
    {
        $name    = $this->connection->quoteIdentifier($column->getName());
        $type    = $column->getType();
        $ddlType = self::DDL_TYPE_MAP[$type] ?? strtoupper($type);

        if (!in_array($type, self::NO_SIZE_TYPES, true) && $column->getSize() !== null) {
            $ddlType .= sprintf('(%s', $column->getSize());
            if ($column->getScale() !== null) {
                $ddlType .= sprintf(',%d', $column->getScale());
            }

            $ddlType .= ')';
        }

        $sql = "{$name} {$ddlType}";

        if ($column->isNotNull()) {
            $sql .= ' NOT NULL';
        }

        if ($column->hasDefault()) {
            $default = $column->getDefault();
            if ($default === null) {
                $sql .= ' DEFAULT NULL';
            } elseif (is_string($default) && in_array(strtoupper($default), ['CURRENT_TIMESTAMP', 'NOW()'], true)) {
                $sql .= ' DEFAULT ' . strtoupper($default);
            } else {
                $sql .= ' DEFAULT ' . $this->connection->quote((string) $default);
            }
        }

        return $sql;
    }

    protected function buildCreateTableSql(string $table, string $schema, array $definition): string
    {
        $t     = $this->qualifyTable($table, $schema);
        $parts = [];

        foreach ($definition['columns'] ?? [] as $column) {
            $col = $this->buildColumnDefinitionSql($column);
            if ($column->isPrimary() && $column->isAutoIncrement()) {
                $col = $this->connection->quoteIdentifier($column->getName()) . ' SERIAL';
            }

            $parts[] = '    ' . $col;
        }

        $hasPrimaryIndex = false;
        foreach ($definition['indexes'] ?? [] as $index) {
            if ($this->isPrimaryIndex($index)) {
                $hasPrimaryIndex = true;
                break;
            }
        }

        if (!$hasPrimaryIndex) {
            $pkCols = [];
            foreach ($definition['columns'] ?? [] as $column) {
                if ($column->isPrimary()) {
                    $pkCols[] = $column->getName();
                }
            }
            if ($pkCols !== []) {
                $parts[] = '    PRIMARY KEY (' . $this->quoteColumns($pkCols) . ')';
            }
        }

        foreach ($definition['indexes'] ?? [] as $index) {
            if ($this->isPrimaryIndex($index)) {
                $cols    = $this->quoteColumns($index->getColumns());
                $parts[] = "    PRIMARY KEY ({$cols})";
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

        return "CREATE TABLE {$t} (\n" . implode(",\n", $parts) . "\n)";
    }

    private function columnToDefinition(Column $col): array
    {
        $def = [
            'type'          => $col->getType(),
            'size'          => $col->getSize(),
            'scale'         => $col->getScale(),
            'notNull'       => $col->isNotNull(),
            'unsigned'      => $col->isUnsigned(),
            'autoIncrement' => $col->isAutoIncrement(),
            'primary'       => $col->isPrimary(),
            'first'         => $col->isFirst(),
            'after'         => $col->getAfterPosition(),
            'comment'       => $col->getComment(),
            'options'       => $col->getOptions(),
        ];

        if ($col->hasDefault()) {
            $def['default'] = $col->getDefault();
        }

        return $def;
    }
}
