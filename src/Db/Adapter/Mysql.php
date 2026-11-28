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

use function array_map;
use function implode;
use function in_array;
use function preg_match;
use function sprintf;
use function str_contains;
use function strtolower;
use function strtoupper;

class Mysql extends AbstractAdapter
{
    /** @var array<string,string> maps Column::TYPE_* → SQL DDL type */
    private const DDL_TYPE_MAP = [
        Column::TYPE_BIGINTEGER   => 'BIGINT',
        Column::TYPE_BIT          => 'BIT',
        Column::TYPE_BLOB         => 'BLOB',
        Column::TYPE_BOOLEAN      => 'TINYINT(1)',
        Column::TYPE_CHAR         => 'CHAR',
        Column::TYPE_DATE         => 'DATE',
        Column::TYPE_DATETIME     => 'DATETIME',
        Column::TYPE_DECIMAL      => 'DECIMAL',
        Column::TYPE_DOUBLE       => 'DOUBLE',
        Column::TYPE_ENUM         => 'ENUM',
        Column::TYPE_FLOAT        => 'FLOAT',
        Column::TYPE_INTEGER      => 'INT',
        Column::TYPE_JSON         => 'JSON',
        Column::TYPE_JSONB        => 'JSON',
        Column::TYPE_LONGBLOB     => 'LONGBLOB',
        Column::TYPE_LONGTEXT     => 'LONGTEXT',
        Column::TYPE_MEDIUMBLOB   => 'MEDIUMBLOB',
        Column::TYPE_MEDIUMINTEGER => 'MEDIUMINT',
        Column::TYPE_MEDIUMTEXT   => 'MEDIUMTEXT',
        Column::TYPE_SET          => 'SET',
        Column::TYPE_SMALLINTEGER => 'SMALLINT',
        Column::TYPE_TEXT         => 'TEXT',
        Column::TYPE_TIME         => 'TIME',
        Column::TYPE_TIMESTAMP    => 'TIMESTAMP',
        Column::TYPE_TINYBLOB     => 'TINYBLOB',
        Column::TYPE_TINYINTEGER  => 'TINYINT',
        Column::TYPE_TINYTEXT     => 'TINYTEXT',
        Column::TYPE_VARCHAR      => 'VARCHAR',
    ];

    private const NO_SIZE_TYPES = [
        Column::TYPE_BLOB,
        Column::TYPE_BOOLEAN,
        Column::TYPE_DATE,
        Column::TYPE_DATETIME,
        Column::TYPE_DOUBLE,
        Column::TYPE_FLOAT,
        Column::TYPE_JSON,
        Column::TYPE_LONGBLOB,
        Column::TYPE_LONGTEXT,
        Column::TYPE_MEDIUMBLOB,
        Column::TYPE_MEDIUMTEXT,
        Column::TYPE_TEXT,
        Column::TYPE_TIME,
        Column::TYPE_TIMESTAMP,
        Column::TYPE_TINYBLOB,
        Column::TYPE_TINYTEXT,
    ];

    /** @var array<string,string> maps info_schema data_type → Column::TYPE_* */
    private const TYPE_MAP = [
        'bigint'            => Column::TYPE_BIGINTEGER,
        'bit'               => Column::TYPE_BIT,
        'blob'              => Column::TYPE_BLOB,
        'char'              => Column::TYPE_CHAR,
        'date'              => Column::TYPE_DATE,
        'datetime'          => Column::TYPE_DATETIME,
        'decimal'           => Column::TYPE_DECIMAL,
        'double'            => Column::TYPE_DOUBLE,
        'enum'              => Column::TYPE_ENUM,
        'float'             => Column::TYPE_FLOAT,
        'int'               => Column::TYPE_INTEGER,
        'json'              => Column::TYPE_JSON,
        'longblob'          => Column::TYPE_LONGBLOB,
        'longtext'          => Column::TYPE_LONGTEXT,
        'mediumblob'        => Column::TYPE_MEDIUMBLOB,
        'mediumint'         => Column::TYPE_MEDIUMINTEGER,
        'mediumtext'        => Column::TYPE_MEDIUMTEXT,
        'set'               => Column::TYPE_SET,
        'smallint'          => Column::TYPE_SMALLINTEGER,
        'text'              => Column::TYPE_TEXT,
        'time'              => Column::TYPE_TIME,
        'timestamp'         => Column::TYPE_TIMESTAMP,
        'tinyblob'          => Column::TYPE_TINYBLOB,
        'tinyint'           => Column::TYPE_TINYINTEGER,
        'tinytext'          => Column::TYPE_TINYTEXT,
        'varchar'           => Column::TYPE_VARCHAR,
    ];
    protected string $currentSchemaSql = 'SELECT DATABASE()';

    public function dropIndex(string $table, string $schema, string $name): void
    {
        $t = $this->qualifyTable($table, $schema);
        $n = $this->connection->quoteIdentifier($name);
        $this->connection->execute("ALTER TABLE {$t} DROP INDEX {$n}");
    }

    public function dropPrimaryKey(string $table, string $schema): void
    {
        $t = $this->qualifyTable($table, $schema);
        $this->connection->execute("ALTER TABLE {$t} DROP PRIMARY KEY");
    }

    public function getTableOptions(string $schema, string $table): array
    {
        $options = $this->connection->fetchOne(
            "SELECT engine           AS engine,
                    table_collation  AS table_collation,
                    auto_increment   AS auto_increment,
                    create_options   AS create_options
             FROM   information_schema.tables
             WHERE  table_schema = :schema
             AND    table_name   = :table",
            ['schema' => $schema, 'table' => $table]
        );

        // The ROW_FORMAT only when the table sets it. The row_format column
        // also gives the default of the server.
        $createOptions = (string) ($options['create_options'] ?? '');
        unset($options['create_options']);
        if (preg_match('/\brow_format=(\w+)/i', $createOptions, $matches) === 1) {
            $options['row_format'] = strtoupper($matches[1]);
        }

        return $options;
    }

    public function listIndexes(string $schema, string $table): array
    {
        $rows    = $this->connection->fetchAll(
            "SELECT INDEX_NAME    AS key_name,
                    COLUMN_NAME   AS column_name,
                    NON_UNIQUE    AS non_unique
             FROM   information_schema.STATISTICS
             WHERE  TABLE_SCHEMA = :schema
             AND    TABLE_NAME   = :table
             ORDER BY INDEX_NAME, SEQ_IN_INDEX",
            ['schema' => $schema, 'table' => $table]
        );

        $groups  = [];
        foreach ($rows as $row) {
            $name              = $row['key_name'];
            $groups[$name]['columns'][]  = $row['column_name'];
            $groups[$name]['non_unique'] = (bool) $row['non_unique'];
        }

        $indexes = [];
        foreach ($groups as $name => $data) {
            if ($name === 'PRIMARY') {
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
            "SELECT kcu.CONSTRAINT_NAME    AS constraint_name,
                    kcu.COLUMN_NAME        AS column_name,
                    kcu.REFERENCED_TABLE_SCHEMA AS referenced_table_schema,
                    kcu.REFERENCED_TABLE_NAME   AS referenced_table_name,
                    kcu.REFERENCED_COLUMN_NAME  AS referenced_column_name,
                    rc.UPDATE_RULE         AS update_rule,
                    rc.DELETE_RULE         AS delete_rule
             FROM   information_schema.KEY_COLUMN_USAGE kcu
             JOIN   information_schema.REFERENTIAL_CONSTRAINTS rc
                    ON  kcu.CONSTRAINT_NAME   = rc.CONSTRAINT_NAME
                    AND kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
             WHERE  kcu.TABLE_SCHEMA = :schema
             AND    kcu.TABLE_NAME   = :table
             AND    kcu.REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION",
            ['schema' => $schema, 'table' => $table]
        );

        $groups = [];
        foreach ($rows as $row) {
            $name = $row['constraint_name'];
            if (!isset($groups[$name])) {
                $groups[$name] = [
                    'referencedSchema'  => $row['referenced_table_schema'],
                    'referencedTable'   => $row['referenced_table_name'],
                    'onUpdate'          => $row['update_rule'],
                    'onDelete'          => $row['delete_rule'],
                    'columns'           => [],
                    'referencedColumns' => [],
                ];
            }

            $groups[$name]['columns'][]           = $row['column_name'];
            $groups[$name]['referencedColumns'][] = $row['referenced_column_name'];
        }

        $references = [];
        foreach ($groups as $name => $data) {
            $references[$name] = new Reference($name, $data);
        }

        return $references;
    }

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

    public function modifyColumn(string $table, string $schema, Column $new, Column $current): void
    {
        $t      = $this->qualifyTable($table, $schema);
        $old    = $this->connection->quoteIdentifier($current->getName());
        $def    = $this->buildColumnDefinitionSql($new);
        $sql    = "ALTER TABLE {$t} CHANGE COLUMN {$old} {$def}";

        if ($new->isFirst()) {
            $sql .= ' FIRST';
        } elseif ($new->getAfterPosition() !== null) {
            $sql .= ' AFTER ' . $this->connection->quoteIdentifier($new->getAfterPosition());
        }

        $this->connection->execute($sql);
    }

    /**
     * information_schema gives the numeric precision as the size of an
     * integer. Use the display width in column_type (int(11)) for the size.
     * MySQL 8.0.19+ gives no display width (int), so the size is null.
     * BOOLEAN (TINYINT(1)) has no size.
     */
    protected function buildColumn(array $row, bool $isFirst, ?string $previous): Column
    {
        $extended = (string) ($row['extended'] ?? '');
        $type     = $this->mapType((string) $row['type'], $extended);

        if (in_array($type, Column::INTEGER_TYPES, true)) {
            $row['size'] = null;
            if ($type !== Column::TYPE_BOOLEAN && preg_match('/^[a-z]+\((\d+)\)/i', $extended, $matches) === 1) {
                $row['size'] = $matches[1];
            }
        }

        // The values define an ENUM or a SET. The size is the length of the longest value.
        if ($type === Column::TYPE_ENUM || $type === Column::TYPE_SET) {
            $row['size'] = null;
        }

        return parent::buildColumn($row, $isFirst, $previous);
    }

    protected function buildColumnDefinitionSql(Column $column): string
    {
        $name    = $this->connection->quoteIdentifier($column->getName());
        $type    = $column->getType();
        $ddlType = self::DDL_TYPE_MAP[$type] ?? strtoupper($type);

        $sql = "{$name} {$ddlType}";

        if (in_array($type, [Column::TYPE_ENUM, Column::TYPE_SET], true) && $column->getOptions() !== null) {
            $values = implode(', ', array_map(
                fn($v) => $this->connection->quote((string) $v),
                $column->getOptions()
            ));
            $sql = "{$name} {$ddlType}({$values})";
        } elseif (!in_array($type, self::NO_SIZE_TYPES, true) && $column->getSize() !== null) {
            $sql .= sprintf('(%s', $column->getSize());
            if ($column->getScale() !== null) {
                $sql .= sprintf(',%d', $column->getScale());
            }

            $sql .= ')';
        }

        if ($column->isUnsigned()) {
            $sql .= ' UNSIGNED';
        }

        if ($column->isNotNull()) {
            $sql .= ' NOT NULL';
        } else {
            $sql .= ' NULL';
        }

        if ($column->hasDefault()) {
            $default = $column->getDefault();
            if ($default === null) {
                $sql .= ' DEFAULT NULL';
            } elseif ($this->isCurrentTimestampDefault($default)) {
                $sql .= ' DEFAULT ' . strtoupper($default);
            } else {
                $sql .= ' DEFAULT ' . $this->connection->quote((string) $default);
            }
        }

        if ($column->isAutoIncrement()) {
            $sql .= ' AUTO_INCREMENT';
        }

        if ($column->getComment() !== '') {
            $sql .= ' COMMENT ' . $this->connection->quote($column->getComment());
        }

        return $sql;
    }

    protected function buildTableOptionsSql(array $options): string
    {
        $parts = [];
        $skip = ['TABLE_TYPE', 'CREATE_TIME', 'UPDATE_TIME', 'CHECK_TIME', 'TABLE_ROWS'];
        foreach ($options as $key => $value) {
            $key = strtoupper($key);
            if ($value === '' || $value === null || in_array($key, $skip, true)) {
                continue;
            }

            if ($key === 'TABLE_COLLATION') {
                $parts[] = 'COLLATE=' . $value;
            } else {
                $parts[] = $key . '=' . $value;
            }
        }

        return $parts !== [] ? ' ' . implode(' ', $parts) : '';
    }

    // -------------------------------------------------------------------------
    // Driver-specific SQL fragments
    // -------------------------------------------------------------------------

    protected function getAutoIncSql(): string
    {
        return "IF(LOCATE('auto_increment', c.extra) > 0, 1, 0)";
    }

    protected function getCommentSql(): string
    {
        return 'c.column_comment';
    }

    protected function getExtendedSql(): string
    {
        return 'c.column_type';
    }

    protected function getUnsignedSql(): string
    {
        return "CASE
            WHEN POSITION('int' IN c.data_type) > 0
                 AND POSITION('unsigned' IN c.column_type) > 0 THEN 1
            WHEN POSITION('int' IN c.data_type) > 0
                 AND POSITION('unsigned' IN c.column_type) = 0 THEN 0
            ELSE NULL
        END";
    }

    protected function mapType(string $infoType, string $extended): string
    {
        $base = strtolower($infoType);

        if ($base === 'tinyint' && str_contains($extended, 'tinyint(1)')) {
            return Column::TYPE_BOOLEAN;
        }

        return self::TYPE_MAP[$base] ?? Column::TYPE_VARCHAR;
    }
}
