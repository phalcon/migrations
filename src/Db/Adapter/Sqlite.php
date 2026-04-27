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

use function preg_match;
use function preg_match_all;
use function str_contains;
use function str_replace;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;
use function trim;

use const PREG_SET_ORDER;

class Sqlite extends AbstractAdapter
{
    private const TYPE_MAP = [
        'bigint'   => Column::TYPE_BIGINTEGER,
        'blob'     => Column::TYPE_BLOB,
        'boolean'  => Column::TYPE_BOOLEAN,
        'char'     => Column::TYPE_CHAR,
        'date'     => Column::TYPE_DATE,
        'datetime' => Column::TYPE_DATETIME,
        'decimal'  => Column::TYPE_DECIMAL,
        'double'   => Column::TYPE_DOUBLE,
        'float'    => Column::TYPE_FLOAT,
        'integer'  => Column::TYPE_INTEGER,
        'int'      => Column::TYPE_INTEGER,
        'longtext' => Column::TYPE_LONGTEXT,
        'numeric'  => Column::TYPE_DECIMAL,
        'real'     => Column::TYPE_FLOAT,
        'smallint' => Column::TYPE_SMALLINTEGER,
        'text'     => Column::TYPE_TEXT,
        'time'     => Column::TYPE_TIME,
        'timestamp' => Column::TYPE_TIMESTAMP,
        'tinyint'  => Column::TYPE_TINYINTEGER,
        'varchar'  => Column::TYPE_VARCHAR,
    ];

    private const DDL_TYPE_MAP = [
        Column::TYPE_BIGINTEGER   => 'INTEGER',
        Column::TYPE_BIT          => 'INTEGER',
        Column::TYPE_BLOB         => 'BLOB',
        Column::TYPE_BOOLEAN      => 'INTEGER',
        Column::TYPE_CHAR         => 'CHARACTER',
        Column::TYPE_DATE         => 'DATE',
        Column::TYPE_DATETIME     => 'DATETIME',
        Column::TYPE_DECIMAL      => 'NUMERIC',
        Column::TYPE_DOUBLE       => 'REAL',
        Column::TYPE_ENUM         => 'TEXT',
        Column::TYPE_FLOAT        => 'REAL',
        Column::TYPE_INTEGER      => 'INTEGER',
        Column::TYPE_JSON         => 'TEXT',
        Column::TYPE_JSONB        => 'TEXT',
        Column::TYPE_LONGBLOB     => 'BLOB',
        Column::TYPE_LONGTEXT     => 'TEXT',
        Column::TYPE_MEDIUMBLOB   => 'BLOB',
        Column::TYPE_MEDIUMINTEGER => 'INTEGER',
        Column::TYPE_MEDIUMTEXT   => 'TEXT',
        Column::TYPE_SMALLINTEGER => 'INTEGER',
        Column::TYPE_TEXT         => 'TEXT',
        Column::TYPE_TIME         => 'TEXT',
        Column::TYPE_TIMESTAMP    => 'DATETIME',
        Column::TYPE_TINYBLOB     => 'BLOB',
        Column::TYPE_TINYINTEGER  => 'INTEGER',
        Column::TYPE_TINYTEXT     => 'TEXT',
        Column::TYPE_VARCHAR      => 'TEXT',
    ];

    public function getCurrentSchema(): string
    {
        return 'main';
    }

    public function listTables(string $schema): array
    {
        $s = $this->quoteName($schema ?: 'main');

        return $this->connection->fetchColumn(
            "SELECT name FROM {$s}.sqlite_master WHERE type = 'table' ORDER BY name"
        );
    }

    public function listIndexes(string $schema, string $table): array
    {
        $s       = $this->quoteName($schema ?: 'main');
        $t       = $this->quoteName($table);
        $list    = $this->connection->fetchAll("PRAGMA {$s}.index_list({$t})");
        $indexes = [];

        foreach ($list as $idx) {
            $name   = $idx['name'];
            $unique = (bool) $idx['unique'];
            $cols   = $this->connection->fetchColumn(
                "PRAGMA {$s}.index_info({$this->quoteName($name)})",
                [],
                2
            );

            $type         = $unique ? Index::TYPE_UNIQUE : '';
            $indexes[$name] = new Index($name, $cols, $type);
        }

        return $indexes;
    }

    public function listReferences(string $schema, string $table): array
    {
        $s    = $this->quoteName($schema ?: 'main');
        $t    = $this->quoteName($table);
        $rows = $this->connection->fetchAll("PRAGMA {$s}.foreign_key_list({$t})");

        $groups = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            if (!isset($groups[$id])) {
                $groups[$id] = [
                    'referencedSchema'  => '',
                    'referencedTable'   => $row['table'],
                    'onUpdate'          => $row['on_update'],
                    'onDelete'          => $row['on_delete'],
                    'columns'           => [],
                    'referencedColumns' => [],
                ];
            }

            $groups[$id]['columns'][]           = $row['from'];
            $groups[$id]['referencedColumns'][] = $row['to'];
        }

        $references = [];
        foreach ($groups as $id => $data) {
            $name              = 'fk_' . $table . '_' . $id;
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
        // SQLite has no ALTER COLUMN — the full table-rebuild approach is outside
        // the scope of this migration library's morph() workflow for SQLite.
        // morphTable() only adds/drops columns at the SQLite level, not modifies.
    }

    public function dropIndex(string $table, string $schema, string $name): void
    {
        $n = $this->connection->quoteIdentifier($name);
        $this->connection->execute("DROP INDEX IF EXISTS {$n}");
    }

    public function dropPrimaryKey(string $table, string $schema): void
    {
        // SQLite does not support dropping a primary key without recreating the table.
    }

    public function addForeignKey(string $table, string $schema, Reference $reference): void
    {
        // SQLite foreign keys are defined at table creation only.
    }

    public function dropForeignKey(string $table, string $schema, string $name): void
    {
        // SQLite does not support dropping foreign keys without recreating the table.
    }

    // -------------------------------------------------------------------------
    // Introspection — SQLite uses PRAGMA, not information_schema
    // -------------------------------------------------------------------------

    public function listColumns(string $schema, string $table): array
    {
        $s       = $this->quoteName($schema ?: 'main');
        $t       = $this->quoteName($table);
        $rows    = $this->connection->fetchAll("PRAGMA {$s}.table_info({$t})");
        $createSql = $this->getTableSql($schema ?: 'main', $table);

        preg_match_all('/^\s*(\w+)\s+.*?AUTOINCREMENT/im', $createSql, $matches, PREG_SET_ORDER);
        $autoIncrementCols = [];
        foreach ($matches as $m) {
            $autoIncrementCols[strtolower($m[1])] = true;
        }

        $columns = [];
        $prev    = null;
        foreach ($rows as $i => $row) {
            $rawType  = strtolower(trim((string) $row['type']));
            $size     = null;
            $scale    = null;

            if (str_contains($rawType, '(') && preg_match('#\((\d+)(?:,\s*(\d+))?\)#', $rawType, $m)) {
                $size  = (int) $m[1];
                $scale = isset($m[2]) ? (int) $m[2] : null;
            }

            $pos = strpos($rawType, '(');
            $baseType = $pos !== false ? substr($rawType, 0, $pos) : $rawType;
            $type     = self::TYPE_MAP[$baseType] ?? Column::TYPE_VARCHAR;

            $defaultRaw = $row['dflt_value'];
            $default    = null;
            $hasDefault = false;
            if ($defaultRaw !== null && strtolower($defaultRaw) !== 'null') {
                $default    = trim($defaultRaw, "'");
                $hasDefault = true;
            }

            $definition = [
                'type'          => $type,
                'size'          => $size,
                'scale'         => $scale,
                'notNull'       => (bool) $row['notnull'],
                'unsigned'      => false,
                'autoIncrement' => isset($autoIncrementCols[strtolower((string) $row['name'])]),
                'primary'       => (bool) $row['pk'],
                'first'         => ($i === 0),
                'after'         => $prev,
                'comment'       => '',
            ];

            if ($hasDefault) {
                $definition['default'] = $default;
            }

            $col                   = new Column((string) $row['name'], $definition);
            $columns[$col->getName()] = $col;
            $prev                  = $col->getName();
        }

        return $columns;
    }

    // -------------------------------------------------------------------------

    protected function mapType(string $infoType, string $extended): string
    {
        return self::TYPE_MAP[strtolower($infoType)] ?? Column::TYPE_VARCHAR;
    }

    protected function buildColumnDefinitionSql(Column $column): string
    {
        $name    = $this->connection->quoteIdentifier($column->getName());
        $type    = $column->getType();
        $ddlType = self::DDL_TYPE_MAP[$type] ?? strtoupper($type);
        $sql     = "{$name} {$ddlType}";

        if ($column->isPrimary() && $column->isAutoIncrement()) {
            return "{$name} INTEGER PRIMARY KEY AUTOINCREMENT";
        }

        if ($column->isNotNull()) {
            $sql .= ' NOT NULL';
        }

        if ($column->hasDefault()) {
            $default = $column->getDefault();
            if ($default === null) {
                $sql .= ' DEFAULT NULL';
            } else {
                $sql .= ' DEFAULT ' . $this->connection->quote((string) $default);
            }
        }

        return $sql;
    }

    protected function buildAddIndexSql(string $table, string $schema, Index $index): string
    {
        $name   = $this->connection->quoteIdentifier($index->getName());
        $t      = $this->qualifyTable($table, $schema);
        $cols   = $this->quoteColumns($index->getColumns());
        $unique = strtolower($index->getType()) === 'unique' ? 'UNIQUE ' : '';

        return "CREATE {$unique}INDEX IF NOT EXISTS {$name} ON {$t} ({$cols})";
    }

    protected function buildCreateTableSql(string $table, string $schema, array $definition): string
    {
        $t     = $this->qualifyTable($table, $schema);
        $parts = [];

        foreach ($definition['columns'] ?? [] as $column) {
            $parts[] = '    ' . $this->buildColumnDefinitionSql($column);
        }

        foreach ($definition['indexes'] ?? [] as $index) {
            if ($index->getType() === Index::TYPE_PRIMARY) {
                $cols    = $this->quoteColumns($index->getColumns());
                $parts[] = "    PRIMARY KEY ({$cols})";
            }
        }

        return "CREATE TABLE {$t} (\n" . implode(",\n", $parts) . "\n)";
    }

    private function getTableSql(string $schema, string $table): string
    {
        $s = $this->quoteName($schema);

        return (string) $this->connection->fetchValue(
            "SELECT sql FROM {$s}.sqlite_master WHERE type = 'table' AND name = :table",
            ['table' => $table]
        );
    }

    private function quoteName(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
