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

namespace Phalcon\Migrations\Migration\Action;

use Generator;
use Phalcon\Db\Column;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\Index;
use Phalcon\Db\IndexInterface;
use Phalcon\Db\ReferenceInterface;
use Phalcon\Migrations\Exception\Db\UnknownColumnTypeException;
use Phalcon\Migrations\Utils;

/**
 * Action class to generate migration file contents
 */
class Generate
{
    /**
     * @var array
     */
    protected $supportedColumnTypes = [
        Column::TYPE_BIGINTEGER => 'TYPE_BIGINTEGER',
        Column::TYPE_INTEGER => 'TYPE_INTEGER',
        Column::TYPE_MEDIUMINTEGER => 'TYPE_MEDIUMINTEGER',
        Column::TYPE_SMALLINTEGER => 'TYPE_SMALLINTEGER',
        Column::TYPE_TINYINTEGER => 'TYPE_TINYINTEGER',

        Column::TYPE_VARCHAR => 'TYPE_VARCHAR',
        Column::TYPE_CHAR => 'TYPE_CHAR',
        Column::TYPE_TEXT => 'TYPE_TEXT',
        Column::TYPE_MEDIUMTEXT => 'TYPE_MEDIUMTEXT',
        Column::TYPE_LONGTEXT => 'TYPE_LONGTEXT',
        Column::TYPE_TINYTEXT => 'TYPE_TINYTEXT',

        Column::TYPE_TIME => 'TYPE_TIME',
        Column::TYPE_DATE => 'TYPE_DATE',
        Column::TYPE_DATETIME => 'TYPE_DATETIME',
        Column::TYPE_TIMESTAMP => 'TYPE_TIMESTAMP',
        Column::TYPE_DECIMAL => 'TYPE_DECIMAL',

        Column::TYPE_BOOLEAN => 'TYPE_BOOLEAN',
        Column::TYPE_FLOAT => 'TYPE_FLOAT',
        Column::TYPE_DOUBLE => 'TYPE_DOUBLE',
        Column::TYPE_TINYBLOB => 'TYPE_TINYBLOB',

        Column::TYPE_BLOB => 'TYPE_BLOB',
        Column::TYPE_MEDIUMBLOB => 'TYPE_MEDIUMBLOB',
        Column::TYPE_LONGBLOB => 'TYPE_LONGBLOB',

        Column::TYPE_JSON => 'TYPE_JSON',
        Column::TYPE_JSONB => 'TYPE_JSONB',
        Column::TYPE_ENUM => 'TYPE_ENUM',
    ];

    /**
     * @var array
     */
    protected $numericColumnTypes = [
        Column::TYPE_INTEGER,
        Column::TYPE_MEDIUMINTEGER,
        Column::TYPE_SMALLINTEGER,
        Column::TYPE_TINYINTEGER,
        Column::TYPE_DECIMAL,
    ];

    /**
     * Column types without size (MySQL / SQLite)
     *
     * @var array
     */
    protected $noSizeColumnTypes = [
        Column::TYPE_DATE,
        Column::TYPE_DATETIME,

        Column::TYPE_TIMESTAMP,
        Column::TYPE_TIME,

        Column::TYPE_FLOAT,
        Column::TYPE_DOUBLE,
        Column::TYPE_DECIMAL,

        Column::TYPE_TINYTEXT,
        Column::TYPE_TEXT,
        Column::TYPE_MEDIUMTEXT,
        Column::TYPE_LONGTEXT,

        Column::TYPE_TINYBLOB,
        Column::TYPE_MEDIUMBLOB,
        Column::TYPE_LONGBLOB,
    ];

    /**
     * Column types without size (PostgreSQL)
     *
     * @var array
     */
    protected $noSizeColumnTypesPostgreSQL = [
        Column::TYPE_BOOLEAN,
        Column::TYPE_INTEGER,
        Column::TYPE_BIGINTEGER,
    ];

    /**
     * SQL Adapter Name
     *
     * @var string
     */
    private $adapter;

    /**
     * Table columns
     *
     * @var array|ColumnInterface[]
     */
    protected $columns;

    /**
     * Table indexes
     *
     * @var array|IndexInterface[]
     */
    protected $indexes;

    /**
     * Table foreign keys and another references
     *
     * @var array|ReferenceInterface[]
     */
    protected $references;

    /**
     * Table options
     *
     * @var array
     */
    protected $options;

    /**
     * @var string|null
     */
    protected $primaryColumnName = null;

    /**
     * Numeric columns
     *
     * Used during exporting of data from table
     *
     * @var array
     */
    protected $numericColumns = [];

    /**
     * Table columns wrapped with "'" single quote symbol
     *
     * @var array
     */
    protected $quoteWrappedColumns = [];

    /**
     * Generate constructor.
     * @param string $adapter
     * @param array|ColumnInterface[] $columns
     * @param array|IndexInterface[] $indexes
     * @param array|ReferenceInterface[] $references
     * @param array $options
     */
    public function __construct(
        string $adapter,
        array $columns = [],
        array $indexes = [],
        array $references = [],
        array $options = []
    ) {
        $this->adapter = $adapter;
        $this->columns = $columns;
        $this->indexes = $indexes;
        $this->references = $references;
        $this->options = $options;
    }

    /**
     * Prepare table columns
     *
     * @throws UnknownColumnTypeException
     * @return Generator
     */
    public function getColumns(): Generator
    {
        $currentColumnName = null;

        foreach ($this->columns as $column) {
            /** @var ColumnInterface $column */

            $columnType = $column->getType();
            if (!isset($this->supportedColumnTypes[$columnType])) {
                throw new UnknownColumnTypeException($column);
            }

            if (in_array($columnType, $this->numericColumnTypes)) {
                $this->numericColumns[$column->getName()] = true;
            }

            $definition = [
                "'type' => Column::" . $this->supportedColumnTypes[$columnType],
            ];

            if ($column->hasDefault() && !$column->isAutoIncrement()) {
                $definition[] = sprintf("'default' => \"%s\"", $column->getDefault());
            }

            if ($column->isPrimary() && $this->adapter == Utils::DB_ADAPTER_POSTGRESQL) {
                $definition[] = "'primary' => true";
                $this->primaryColumnName = $column->getName();
            }

            if ($column->isUnsigned()) {
                $definition[] = "'unsigned' => true";
            }

            if ($column->isNotNull()) {
                $definition[] = "'notNull' => true";
            } elseif (!$column->isPrimary()) {
                // A primary key column cannot have NULL values.
                $definition[] = "'notNull' => false";
            }

            if ($column->isAutoIncrement()) {
                $definition[] = "'autoIncrement' => true";
            }

            /**
             * Define column size
             */
            $columnSize = $this->getColumnSize($column);
            if ($columnSize !== null) {
                $definition[] = "'size' => $columnSize";
            }

            if ($column->getScale()) {
                $definition[] = "'scale' => " . $column->getScale();
            }

            $this->quoteWrappedColumns[] = $this->wrapWithQuotes($column->getName());

            /**
             * Aggregate column definition
             */
            $definition[] = $currentColumnName === null ? "'first' => true" : "'after' => '" . $currentColumnName . "'";
            $currentColumnName = $column->getName();

            yield $column->getName() => $definition;
        }
    }

    /**
     * @return Generator
     */
    public function getIndexes(): Generator
    {
        foreach ($this->indexes as $name => $index) {
        /** @var Index $index */
            $definition = [];
            foreach ($index->getColumns() as $column) {
                // [PostgreSQL] Skip primary key column
                if ($this->adapter !== Utils::DB_ADAPTER_POSTGRESQL && $column !== $this->getPrimaryColumnName()) {
                    $definition[] = $this->wrapWithQuotes($column);
                }
            }

            if (!empty($definition)) {
                yield $name => [$definition, $index->getType()];
            }
        }
    }

    /**
     * @return Generator
     */
    public function getReferences(): Generator
    {
        foreach ($this->references as $constraintName => $reference) {
            $referenceColumns = [];
            foreach ($reference->getColumns() as $column) {
                $referenceColumns[] = sprintf("'%s'", $column);
            }

            $referencedColumns = [];
            foreach ($reference->getReferencedColumns() as $referencedColumn) {
                $referencedColumns[] = $this->wrapWithQuotes($referencedColumn);
            }
            
            yield $constraintName => [
                sprintf("'referencedTable' => %s", $this->wrapWithQuotes($reference->getReferencedTable())),
                sprintf("'referencedSchema' => %s", $this->wrapWithQuotes($reference->getReferencedSchema())),
                "'columns' => [" . join(',', array_unique($referenceColumns)) . "]",
                "'referencedColumns' => [" . join(',', array_unique($referencedColumns)) . "]",
                sprintf("'onUpdate' => '%s'", $reference->getOnUpdate()),
                sprintf("'onDelete' => '%s'", $reference->getOnDelete()),
            ];
        }
    }

    /**
     * @param bool $skipAI Skip Auto Increment
     * @return array
     */
    public function getOptions(bool $skipAI): array
    {
        $options = [];
        foreach ($this->options as $name => $value) {
            if ($skipAI && strtoupper($name) == 'AUTO_INCREMENT') {
                $value = '';
            }

            $options[] = sprintf('%s => %s', $this->wrapWithQuotes($name), $this->wrapWithQuotes((string)$value));
        }
        
        return $options;
    }

    /**
     * Get Primary column name (if exists)
     *
     * @return string|null
     */
    public function getPrimaryColumnName(): ?string
    {
        return $this->primaryColumnName;
    }

    /**
     * @return array
     */
    public function getNumericColumns(): array
    {
        return $this->numericColumns;
    }

    /**
     * @return string
     */
    public function getAdapter(): string
    {
        return $this->adapter;
    }

    /**
     * Just wrap string with single quotes
     *
     * @param string $columnName
     * @param string $quote
     * @return string
     */
    public function wrapWithQuotes(string $columnName, string $quote = "'"): string
    {
        return $quote . $columnName . $quote;
    }

    /**
     * @return array
     */
    public function getQuoteWrappedColumns(): array
    {
        return $this->quoteWrappedColumns;
    }

    /**
     * Get column size basing on its type
     *
     * @param ColumnInterface $column
     * @return int|string|null
     */
    protected function getColumnSize(ColumnInterface $column)
    {
        $columnType = $column->getType();
        $columnsSize = $column->getSize();

        /**
         * Check Postgres
         */
        $noSizePostgres = $this->noSizeColumnTypesPostgreSQL;
        if ($this->adapter === Utils::DB_ADAPTER_POSTGRESQL && in_array($columnType, $noSizePostgres)) {
            return null;
        }

        /**
         * Check MySQL and SQLite
         */
        if (in_array($columnType, $this->noSizeColumnTypes)) {
            return null;
        }

        if ($columnType === Column::TYPE_ENUM) {
            $size = $this->wrapWithQuotes((string)$columnsSize, '"');
        } else {
            $size = $columnsSize ?: 1;
        }

        return $size;
    }
}
