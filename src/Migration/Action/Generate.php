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
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Phalcon\Db\Adapter\AbstractAdapter;
use Phalcon\Db\Column;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\Enum;
use Phalcon\Db\Exception;
use Phalcon\Db\Index;
use Phalcon\Db\IndexInterface;
use Phalcon\Db\Reference;
use Phalcon\Db\ReferenceInterface;
use Phalcon\Migrations\Exception\Db\UnknownColumnTypeException;
use Phalcon\Migrations\Exception\RuntimeException;
use Phalcon\Migrations\Generator\Snippet;
use Phalcon\Migrations\Mvc\Model\Migration;
use Phalcon\Migrations\Version\ItemInterface;

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
    protected $supportedColumnTypesPgsql = [
        Column::TYPE_DOUBLE => 'TYPE_FLOAT',
    ];

    /**
     * @var array
     */
    protected $supportedColumnTypesMysql = [
        Column::TYPE_DOUBLE => 'TYPE_DOUBLE',
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
     * Migration file entity
     *
     * @var PhpFile
     */
    private $file;

    /**
     * Migration class entity
     *
     * @var ClassType
     */
    private $class;

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

    public function getEntity(): PhpFile
    {
        $this->checkEntityExists();

        return $this->file;
    }

    public function createEntity(string $className, bool $recreate = false): self
    {
        if (null === $this->class || $recreate) {
            $this->file = new PhpFile();
            $this->file->addUse(Column::class)
                ->addUse(Exception::class)
                ->addUse(Index::class)
                ->addUse(Reference::class)
                ->addUse(Migration::class);

            $this->class = $this->file->addClass($className);
            $this->class
                ->setExtends(Migration::class)
                ->addComment("Class {$className}");
        }

        return $this;
    }

    /**
     * @throws UnknownColumnTypeException
     */
    public function addMorph(Snippet $snippet, string $table, bool $skipRefSchema = false, bool $skipAI = true): self
    {
        $this->checkEntityExists();

        $columns = [];
        foreach ($this->getColumns() as $columnName => $columnDefinition) {
            $definitions = implode(",\n                ", $columnDefinition);
            $columns[] = sprintf($snippet->getColumnTemplate(), $columnName, $definitions);
        }

        $indexes = [];
        foreach ($this->getIndexes() as $indexName => $indexDefinition) {
            [$fields, $indexType] = $indexDefinition;
            $definitions = implode(", ", $fields);
            $type = $indexType ? "'$indexType'" : "''";
            $indexes[] = sprintf($snippet->getIndexTemplate(), $indexName, $definitions, $type);
        }

        $references = [];
        foreach ($this->getReferences($skipRefSchema) as $constraintName => $referenceDefinition) {
            $definitions = implode(",\n                ", $referenceDefinition);
            $references[] = sprintf($snippet->getReferenceTemplate(), $constraintName, $definitions);
        }

        $options = [];
        foreach ($this->getOptions($skipAI) as $option) {
            $options[] = sprintf($snippet->getOptionTemplate(), $option);
        }

        $body = sprintf(
            $snippet->getMorphTemplate(),
            $table,
            $snippet->definitionToString('columns', $columns)
            . $snippet->definitionToString('indexes', $indexes)
            . $snippet->definitionToString('references', $references)
            . $snippet->definitionToString('options', $options)
        );

        $this->class->addMethod('morph')
            ->addComment("Define the table structure\n")
            ->addComment('@return void')
            ->addComment('@throws Exception')
            ->setReturnType('void')
            ->setBody($body);

        return $this;
    }

    public function addUp(string $table, $exportData = null, bool $shouldExportDataFromTable = false): self
    {
        $this->checkEntityExists();

        $body = "\n";
        if ($exportData === 'always' || $shouldExportDataFromTable) {
            $quoteWrappedColumns = "\n";
            foreach ($this->quoteWrappedColumns as $quoteWrappedColumn) {
                $quoteWrappedColumns .= "    $quoteWrappedColumn,\n";
            }
            $body = "\$this->batchInsert('$table', [{$quoteWrappedColumns}]);";
        }

        $this->class->addMethod('up')
            ->addComment("Run the migrations\n")
            ->addComment('@return void')
            ->setReturnType('void')
            ->setBody($body);

        return $this;
    }

    public function addDown(string $table, $exportData = null, bool $shouldExportDataFromTable = false): self
    {
        $this->checkEntityExists();

        $body = "\n";
        if ($exportData === 'always' || $shouldExportDataFromTable) {
            $body = "\$this->batchDelete('$table');";
        }

        $this->class->addMethod('down')
            ->addComment("Reverse the migrations\n")
            ->addComment('@return void')
            ->setReturnType('void')
            ->setBody($body);

        return $this;
    }

    public function addAfterCreateTable(string $table, $exportData = null): self
    {
        $this->checkEntityExists();

        if ($exportData === 'oncreate') {
            $quoteWrappedColumns = "\n";
            foreach ($this->quoteWrappedColumns as $quoteWrappedColumn) {
                $quoteWrappedColumns .= "    $quoteWrappedColumn,\n";
            }
            $body = "\$this->batchInsert('$table', [{$quoteWrappedColumns}]);";

            $this->class->addMethod('afterCreateTable')
                ->addComment("This method is called after the table was created\n")
                ->addComment('@return void')
                ->setReturnType('void')
                ->setBody($body);
        }

        return $this;
    }

    public function createDumpFiles(
        string $table,
        string $migrationPath,
        AbstractAdapter $connection,
        ItemInterface $version,
        $exportData = null,
        bool $shouldExportDataFromTable = false
    ): self {
        $numericColumns = $this->getNumericColumns();
        if ($exportData === 'always' || $exportData === 'oncreate' || $shouldExportDataFromTable) {
            $fileHandler = fopen($migrationPath . $version->getVersion() . '/' . $table . '.dat', 'w');
            $cursor = $connection->query('SELECT * FROM ' . $connection->escapeIdentifier($table));
            $cursor->setFetchMode(Enum::FETCH_ASSOC);
            while ($row = $cursor->fetchArray()) {
                $data = [];
                foreach ($row as $key => $value) {
                    if (isset($numericColumns[$key])) {
                        if ($value === '' || $value === null) {
                            $data[] = 'NULL';
                        } else {
                            $data[] = $value;
                        }
                    } elseif (is_string($value)) {
                        $data[] = addslashes($value);
                    } else {
                        $data[] = $value ?? 'NULL';
                    }

                    unset($value);
                }

                fputcsv($fileHandler, $data);
                unset($row, $data);
            }

            fclose($fileHandler);
        }

        return $this;
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
        if ($this->adapter === Migration::DB_ADAPTER_POSTGRESQL) {
            $supportedColumnTypes = \array_replace($this->supportedColumnTypes, $this->supportedColumnTypesPgsql);
        } elseif ($this->adapter === Migration::DB_ADAPTER_MYSQL) {
            $supportedColumnTypes = \array_replace($this->supportedColumnTypes, $this->supportedColumnTypesMysql);
        } else {
            $supportedColumnTypes = $this->supportedColumnTypes;
        }

        foreach ($this->columns as $column) {
            /** @var ColumnInterface $column */

            $columnType = $column->getType();
            if (!isset($supportedColumnTypes[$columnType])) {
                throw new UnknownColumnTypeException($column);
            }

            if (in_array($columnType, $this->numericColumnTypes, true)) {
                $this->numericColumns[$column->getName()] = true;
            }

            $definition = [
                "'type' => Column::" . $supportedColumnTypes[$columnType],
            ];

            if ($column->hasDefault() && !$column->isAutoIncrement()) {
                $definition[] = sprintf("'default' => \"%s\"", $column->getDefault());
            }

            if ($this->adapter === Migration::DB_ADAPTER_POSTGRESQL && $column->isPrimary()) {
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

            if (method_exists($column, 'getComment') && $column->getComment()) {
                $definition[] = sprintf("'comment' => \"%s\"", $column->getComment());
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
                if ($column !== $this->getPrimaryColumnName()) {
                    $definition[] = $this->wrapWithQuotes($column);
                }
            }

            if (!empty($definition)) {
                yield $name => [$definition, $index->getType()];
            }
        }
    }

    /**
     * @param bool $skipRefSchema
     * @return Generator
     */
    public function getReferences(bool $skipRefSchema = false): Generator
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

            $referencesOptions = [];
            $referencedSchema = $reference->getReferencedSchema();
            if ($skipRefSchema === false && $referencedSchema !== null) {
                $referencesOptions[] = sprintf(
                    "'referencedSchema' => %s",
                    $this->wrapWithQuotes($referencedSchema)
                );
            }

            yield $constraintName => array_merge($referencesOptions, [
                sprintf("'referencedTable' => %s", $this->wrapWithQuotes($reference->getReferencedTable())),
                "'columns' => [" . join(',', array_unique($referenceColumns)) . "]",
                "'referencedColumns' => [" . join(',', array_unique($referencedColumns)) . "]",
                sprintf("'onUpdate' => '%s'", $reference->getOnUpdate()),
                sprintf("'onDelete' => '%s'", $reference->getOnDelete()),
            ]);
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
            /**
             * All options keys must be UPPERCASE!
             */
            $name = strtoupper($name);
            if ($skipAI && $name === 'AUTO_INCREMENT') {
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
        if ($this->adapter === Migration::DB_ADAPTER_POSTGRESQL && in_array($columnType, $noSizePostgres)) {
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

    public function checkEntityExists(): void
    {
        if (null === $this->file) {
            throw new RuntimeException('Migration entity is e,pty. Call Generate::createEntity()');
        }
    }
}
