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

namespace Phalcon\Migrations\Mvc\Model;

use DirectoryIterator;
use Exception;
use Phalcon\Config;
use Phalcon\Db\Adapter\AbstractAdapter;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Db\Adapter\Pdo\Postgresql as PdoPostgresql;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\Enum;
use Phalcon\Db\Exception as DbException;
use Phalcon\Db\IndexInterface;
use Phalcon\Db\ReferenceInterface;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Migrations\Exception\Db\UnknownColumnTypeException;
use Phalcon\Migrations\Exception\RuntimeException;
use Phalcon\Migrations\Generator\Snippet;
use Phalcon\Migrations\Listeners\DbProfilerListener;
use Phalcon\Migrations\Migration\Action\Generate as GenerateAction;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Version\ItemCollection as VersionCollection;
use Phalcon\Migrations\Version\ItemInterface;
use Phalcon\Text;

/**
 * Migrations of DML y DDL over databases
 *
 * @method afterCreateTable()
 * @method morph()
 * @method up()
 * @method afterUp()
 * @method down()
 * @method afterDown()
 */
class Migration
{
    public const DIRECTION_FORWARD = 1;
    public const DIRECTION_BACK = -1;

    public const DB_ADAPTER_MYSQL = 'mysql';
    public const DB_ADAPTER_POSTGRESQL = 'postgresql';
    public const DB_ADAPTER_SQLITE = 'sqlite';

    /**
     * Migration database connection
     *
     * @var AbstractAdapter
     */
    protected static $connection;

    /**
     * Database configuration
     *
     * @var Config
     */
    private static $databaseConfig;

    /**
     * Path where to save the migration
     *
     * @var string
     */
    private static $migrationPath = '';

    /**
     * Skip auto increment
     *
     * @var bool
     */
    private static $skipAI = true;

    /**
     * Version of the migration file
     *
     * @var string|null
     */
    protected $version = null;

    /**
     * Prepares component
     *
     * @param Config $database Database config
     * @param bool $verbose array with settings
     * @throws DbException
     */
    public static function setup(Config $database, bool $verbose = false): void
    {
        if (!isset($database->adapter)) {
            throw new DbException('Unspecified database Adapter in your configuration!');
        }

        /**
         * The original Phalcon\Db\Adapter\Pdo\Mysql::addForeignKey is broken until the v3.2.0
         *
         * @see: Phalcon\Db\Dialect\PdoMysql The extended and fixed dialect class for MySQL
         */
        if ($database->adapter == 'Mysql') {
            $adapter = PdoMysql::class;
        } elseif ($database->adapter == 'Postgresql') {
            $adapter = PdoPostgresql::class;
        } else {
            $adapter = '\\Phalcon\\Db\\Adapter\\Pdo\\' . $database->adapter;
        }

        if (!class_exists($adapter)) {
            throw new DbException("Invalid database adapter: '{$adapter}'");
        }

        $configArray = $database->toArray();
        unset($configArray['adapter']);

        /** @var AbstractAdapter connection */
        self::$connection = new $adapter($configArray);
        self::$databaseConfig = $database;

        if (!Migrations::isConsole() || !$verbose) {
            return;
        }

        $eventsManager = new EventsManager();
        $eventsManager->attach('db', new DbProfilerListener());

        self::$connection->setEventsManager($eventsManager);
    }

    /**
     * Set the skip auto increment value
     *
     * @param bool $skip
     */
    public static function setSkipAutoIncrement(bool $skip): void
    {
        self::$skipAI = $skip;
    }

    /**
     * Set the migration directory path
     *
     * @param string $path
     */
    public static function setMigrationPath(string $path): void
    {
        self::$migrationPath = rtrim($path, '\\/') . DIRECTORY_SEPARATOR;
    }

    /**
     * Generates all the class migration definitions for certain database setup
     *
     * @param ItemInterface $version
     * @param string $exportData
     * @param array $exportTables
     * @param bool $skipRefSchema
     * @return array
     * @throws UnknownColumnTypeException
     */
    public static function generateAll(
        ItemInterface $version,
        string $exportData = null,
        array $exportTables = [],
        bool $skipRefSchema = false
    ): array {
        $classDefinition = [];
        $schema = self::resolveDbSchema(self::$databaseConfig);

        foreach (self::$connection->listTables($schema) as $table) {
            $classDefinition[$table] = self::generate($version, $table, $exportData, $exportTables, $skipRefSchema);
        }

        return $classDefinition;
    }

    /**
     * Generate specified table migration
     *
     * @param ItemInterface $version
     * @param string $table
     * @param mixed $exportData
     * @param array $exportTables
     * @param bool $skipRefSchema
     * @return string
     * @throws UnknownColumnTypeException
     */
    public static function generate(
        ItemInterface $version,
        string $table,
        $exportData = null,
        array $exportTables = [],
        bool $skipRefSchema = false
    ): string {
        $snippet = new Snippet();
        $adapter = (string)self::$databaseConfig->path('adapter');
        $defaultSchema = self::resolveDbSchema(self::$databaseConfig);
        $description = self::$connection->describeColumns($table, $defaultSchema);
        $indexes = self::$connection->describeIndexes($table, $defaultSchema);
        $references = self::$connection->describeReferences($table, $defaultSchema);
        $tableOptions = self::$connection->tableOptions($table, $defaultSchema);

        $generateAction = new GenerateAction($adapter, $description, $indexes, $references, $tableOptions);

        /**
         * Generate Columns
         */
        $tableDefinition = [];
        foreach ($generateAction->getColumns() as $columnName => $columnDefinition) {
            $tableDefinition[] = $snippet->getColumnDefinition($columnName, $columnDefinition);
        }

        /**
         * Generate Indexes
         */
        $indexesDefinition = [];
        foreach ($generateAction->getIndexes() as $indexName => $indexDefinition) {
            list($definition, $type) = $indexDefinition;
            $indexesDefinition[] = $snippet->getIndexDefinition($indexName, $definition, $type);
        }

        /**
         * Generate References
         */
        $referencesDefinition = [];
        foreach ($generateAction->getReferences($skipRefSchema) as $constraintName => $referenceDefinition) {
            $referencesDefinition[] = $snippet->getReferenceDefinition($constraintName, $referenceDefinition);
        }

        /**
         * Generate Options
         */
        $optionsDefinition = $generateAction->getOptions(self::$skipAI);

        $classVersion = preg_replace('/[^0-9A-Za-z]/', '', (string)$version->getStamp());
        $className = Text::camelize($table) . 'Migration_' . $classVersion;

        // morph()
        $classData = $snippet->getMigrationMorph($className, $table, $tableDefinition);

        if (count($indexesDefinition) > 0) {
            $classData .= $snippet->getMigrationDefinition('indexes', $indexesDefinition);
        }

        if (count($referencesDefinition) > 0) {
            $classData .= $snippet->getMigrationDefinition('references', $referencesDefinition);
        }

        if (count($optionsDefinition) > 0) {
            $classData .= $snippet->getMigrationDefinition('options', $optionsDefinition);
        }

        $classData .= "            ]\n        );\n    }\n";

        // up()
        $classData .= $snippet->getMigrationUp();

        if ($exportData === 'always' || self::shouldExportDataFromTable($table, $exportTables)) {
            $classData .= $snippet->getMigrationBatchInsert($table, $generateAction->getQuoteWrappedColumns());
        }

        $classData .= "\n    }\n";

        // down()
        $classData .= $snippet->getMigrationDown();

        if ($exportData == 'always' || self::shouldExportDataFromTable($table, $exportTables)) {
            $classData .= $snippet->getMigrationBatchDelete($table);
        }

        $classData .= "\n    }\n";

        // afterCreateTable()
        if ($exportData === 'oncreate') {
            $classData .= $snippet->getMigrationAfterCreateTable($table, $generateAction->getQuoteWrappedColumns());
        }

        // end of class
        $classData .= "\n}\n";

        $numericColumns = $generateAction->getNumericColumns();
        // dump data
        if (
            $exportData == 'always' ||
            $exportData == 'oncreate' ||
            self::shouldExportDataFromTable($table, $exportTables)
        ) {
            $fileHandler = fopen(self::$migrationPath . $version->getVersion() . '/' . $table . '.dat', 'w');
            $cursor = self::$connection->query('SELECT * FROM ' . self::$connection->escapeIdentifier($table));
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
                    } else {
                        if (is_string($value)) {
                            $data[] = addslashes($value);
                        } else {
                            $data[] = $value === null ? 'NULL' : $value;
                        }
                    }

                    unset($value);
                }

                fputcsv($fileHandler, $data);
                unset($row);
                unset($data);
            }

            fclose($fileHandler);
        }

        return $classData;
    }

    public static function shouldExportDataFromTable(string $table, array $exportTables): bool
    {
        return in_array($table, $exportTables);
    }

    /**
     * Migrate
     *
     * @param string $tableName
     * @param ItemInterface|null $fromVersion
     * @param ItemInterface|null $toVersion
     * @param bool $skipForeignChecks
     * @throws Exception
     */
    public static function migrate(
        string $tableName,
        ItemInterface $fromVersion = null,
        ItemInterface $toVersion = null,
        bool $skipForeignChecks = false
    ): void {
        $fromVersion = $fromVersion ?: VersionCollection::createItem($fromVersion);
        $toVersion = $toVersion ?: VersionCollection::createItem($toVersion);

        if ($fromVersion->getStamp() == $toVersion->getStamp()) {
            return; // nothing to do
        }

        if ($skipForeignChecks === true) {
            self::$connection->execute('SET FOREIGN_KEY_CHECKS=0');
        }

        if ($fromVersion->getStamp() < $toVersion->getStamp()) {
            $toMigration = self::createClass($toVersion, $tableName);

            if (is_object($toMigration)) {
                // morph the table structure
                if (method_exists($toMigration, 'morph')) {
                    $toMigration->morph();
                }

                // modify the datasets
                if (method_exists($toMigration, 'up')) {
                    $toMigration->up();
                    if (method_exists($toMigration, 'afterUp')) {
                        $toMigration->afterUp();
                    }
                }
            }
        } else {
            // rollback!

            // reset the data modifications
            $fromMigration = self::createClass($fromVersion, $tableName);
            if (is_object($fromMigration) && method_exists($fromMigration, 'down')) {
                $fromMigration->down();

                if (method_exists($fromMigration, 'afterDown')) {
                    $fromMigration->afterDown();
                }
            }

            // call the last morph function in the previous migration files
            $toMigration = self::createPrevClassWithMorphMethod($toVersion, $tableName);
            if ($toMigration !== null && method_exists($toMigration, 'morph')) {
                $toMigration->morph();
            }
        }

        if ($skipForeignChecks === true) {
            self::$connection->execute('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Create migration object for specified version
     *
     * @param ItemInterface $version
     * @param string $tableName
     * @return null|Migration
     * @throws Exception
     */
    private static function createClass(ItemInterface $version, string $tableName): ?Migration
    {
        $fileName = self::$migrationPath . $version->getVersion() . DIRECTORY_SEPARATOR . $tableName . '.php';
        if (!file_exists($fileName)) {
            return null;
        }

        $className = Text::camelize($tableName) . 'Migration_' . $version->getStamp();

        include_once $fileName;
        if (!class_exists($className)) {
            throw new Exception('Migration class cannot be found ' . $className . ' at ' . $fileName);
        }

        /** @var Migration $migration */
        $migration = new $className($version);
        $migration->version = $version->__toString();

        return $migration;
    }

    /**
     * Find the last morph function in the previous migration files
     *
     * @param ItemInterface $toVersion
     * @param string $tableName
     *
     * @return null|Migration
     * @throws Exception
     * @internal param ItemInterface $version
     */
    private static function createPrevClassWithMorphMethod(ItemInterface $toVersion, string $tableName): ?Migration
    {
        $prevVersions = [];
        $versions = self::scanForVersions(self::$migrationPath);
        foreach ($versions as $prevVersion) {
            if ($prevVersion->getStamp() <= $toVersion->getStamp()) {
                $prevVersions[] = $prevVersion;
            }
        }

        $prevVersions = VersionCollection::sortDesc($prevVersions);
        foreach ($prevVersions as $prevVersion) {
            $migration = self::createClass($prevVersion, $tableName);
            if (is_object($migration) && method_exists($migration, 'morph')) {
                return $migration;
            }
        }

        return null;
    }

    /**
     * Scan for all versions
     *
     * @param string $dir Directory to scan
     * @return ItemInterface[]
     */
    public static function scanForVersions(string $dir): array
    {
        $versions = [];
        $iterator = new DirectoryIterator($dir);
        foreach ($iterator as $fileInfo) {
            $filename = $fileInfo->getFilename();
            if (!$fileInfo->isDir() || $fileInfo->isDot() || !VersionCollection::isCorrectVersion($filename)) {
                continue;
            }

            $versions[] = VersionCollection::createItem($filename);
        }

        return $versions;
    }

    /**
     * Look for table definition modifications and apply to real table
     *
     * @param string $tableName
     * @param array $definition
     *
     * @throws DbException
     */
    public function morphTable(string $tableName, array $definition): void
    {
        $defaultSchema = self::resolveDbSchema(self::$databaseConfig);
        $tableExists = self::$connection->tableExists($tableName, $defaultSchema);
        $tableSchema = (string)$defaultSchema;

        if (isset($definition['columns'])) {
            if (count($definition['columns']) == 0) {
                throw new DbException('Table must have at least one column');
            }

            $fields = [];
            /** @var ColumnInterface $tableColumn */
            foreach ($definition['columns'] as $tableColumn) {
                /**
                 * TODO: Remove this message, as it will throw same during createTable() execution.
                 */
                if (!is_object($tableColumn)) {
                    throw new DbException('Table must have at least one column');
                }

                /** @var ColumnInterface[] $fields */
                $fields[$tableColumn->getName()] = $tableColumn;
            }

            if ($tableExists) {
                $localFields = [];
                /** @var ColumnInterface[] $description */
                $description = self::$connection->describeColumns($tableName, $defaultSchema);
                foreach ($description as $field) {
                    /** @var ColumnInterface[] $localFields */
                    $localFields[$field->getName()] = $field;
                }

                foreach ($fields as $fieldName => $column) {
                    if (!isset($localFields[$fieldName])) {
                        try {
                            self::$connection->addColumn($tableName, $tableSchema, $column);
                        } catch (\Throwable $exception) {
                            throw new RuntimeException(
                                sprintf(
                                    "Failed to add column '%s' in table '%s'. In '%s' migration. DB error: %s",
                                    $column->getName(),
                                    $tableName,
                                    \get_called_class(),
                                    $exception->getMessage()
                                )
                            );
                        }

                        continue;
                    }

                    $changed = false;
                    if ($localFields[$fieldName]->getType() != $column->getType()) {
                        $changed = true;
                    }

                    if ($localFields[$fieldName]->getSize() != $column->getSize()) {
                        $changed = true;
                    }

                    if ($localFields[$fieldName]->isNotNull() != $column->isNotNull()) {
                        $changed = true;
                    }

                    if ($localFields[$fieldName]->getDefault() != $column->getDefault()) {
                        $changed = true;
                    }

                    if ($changed === true) {
                        try {
                            self::$connection->modifyColumn($tableName, $tableSchema, $column, $column);
                        } catch (\Throwable $exception) {
                            throw new RuntimeException(
                                sprintf(
                                    "Failed to modify column '%s' in table '%s'. In '%s' migration. DB error: %s",
                                    $column->getName(),
                                    $tableName,
                                    \get_called_class(),
                                    $exception->getMessage()
                                )
                            );
                        }
                    }
                }

                foreach ($localFields as $fieldName => $localField) {
                    if (!isset($fields[$fieldName])) {
                        try {
                            /**
                             * TODO: Check, why schemaName is empty string.
                             */
                            self::$connection->dropColumn($tableName, '', $fieldName);
                        } catch (\Throwable $exception) {
                            throw new RuntimeException(
                                sprintf(
                                    "Failed to drop column '%s' in table '%s'. In '%s' migration. DB error: %s",
                                    $fieldName,
                                    $tableName,
                                    \get_called_class(),
                                    $exception->getMessage()
                                )
                            );
                        }
                    }
                }
            } else {
                try {
                    self::$connection->createTable($tableName, $tableSchema, $definition);
                } catch (\Throwable $exception) {
                    throw new RuntimeException(
                        sprintf(
                            "Failed to create table '%s'. In '%s' migration. DB error: %s",
                            $tableName,
                            \get_called_class(),
                            $exception->getMessage()
                        )
                    );
                }

                if (method_exists($this, 'afterCreateTable')) {
                    $this->afterCreateTable();
                }
            }
        }

        if (isset($definition['references']) && $tableExists) {
            $references = [];
            foreach ($definition['references'] as $tableReference) {
                $references[$tableReference->getName()] = $tableReference;
            }

            $localReferences = [];
            $activeReferences = self::$connection->describeReferences($tableName, $defaultSchema);
            /** @var ReferenceInterface $activeReference */
            foreach ($activeReferences as $activeReference) {
                $localReferences[$activeReference->getName()] = [
                    'columns' => $activeReference->getColumns(),
                    'referencedTable' => $activeReference->getReferencedTable(),
                    'referencedSchema' => $activeReference->getReferencedSchema(),
                    'referencedColumns' => $activeReference->getReferencedColumns(),
                ];
            }

            foreach ($definition['references'] as $tableReference) {
                $schemaName = $tableReference->getSchemaName() ?? '';

                if (!isset($localReferences[$tableReference->getName()])) {
                    try {
                        self::$connection->addForeignKey(
                            $tableName,
                            $schemaName,
                            $tableReference
                        );
                    } catch (\Throwable $exception) {
                        throw new RuntimeException(
                            sprintf(
                                "Failed to add foreign key '%s' in '%s'. In '%s' migration. DB error: %s",
                                $tableReference->getName(),
                                $tableName,
                                \get_called_class(),
                                $exception->getMessage()
                            )
                        );
                    }

                    continue;
                }

                $changed = false;
                if (
                    $tableReference->getReferencedTable() !=
                    $localReferences[$tableReference->getName()]['referencedTable']
                ) {
                    $changed = true;
                }

                if (!$changed) {
                    if (
                        count($tableReference->getColumns()) !=
                        count($localReferences[$tableReference->getName()]['columns'])
                    ) {
                        $changed = true;
                    }
                }

                if (!$changed) {
                    if (
                        count($tableReference->getReferencedColumns()) !=
                        count($localReferences[$tableReference->getName()]['referencedColumns'])
                    ) {
                        $changed = true;
                    }
                }

                if (!$changed) {
                    foreach ($tableReference->getColumns() as $columnName) {
                        if (!in_array($columnName, $localReferences[$tableReference->getName()]['columns'])) {
                            $changed = true;
                            break;
                        }
                    }
                }

                if (!$changed) {
                    foreach ($tableReference->getReferencedColumns() as $columnName) {
                        if (!in_array($columnName, $localReferences[$tableReference->getName()]['referencedColumns'])) {
                            $changed = true;
                            break;
                        }
                    }
                }

                if ($changed) {
                    try {
                        self::$connection->dropForeignKey(
                            $tableName,
                            $schemaName,
                            $tableReference->getName()
                        );
                    } catch (\Throwable $exception) {
                        throw new RuntimeException(
                            sprintf(
                                "Failed to drop foreign key '%s' in '%s'. In '%s' migration. DB error: %s",
                                $tableReference->getName(),
                                $tableName,
                                \get_called_class(),
                                $exception->getMessage()
                            )
                        );
                    }

                    try {
                        self::$connection->addForeignKey(
                            $tableName,
                            $schemaName,
                            $tableReference
                        );
                    } catch (\Throwable $exception) {
                        throw new RuntimeException(
                            sprintf(
                                "Failed to add foreign key '%s' in '%s'. In '%s' migration. DB error: %s",
                                $tableReference->getName(),
                                $tableName,
                                \get_called_class(),
                                $exception->getMessage()
                            )
                        );
                    }
                }
            }

            foreach ($localReferences as $referenceName => $reference) {
                if (!isset($references[$referenceName])) {
                    try {
                        /**
                         * TODO: Check, why schemaName is empty string.
                         */
                        self::$connection->dropForeignKey($tableName, '', $referenceName);
                    } catch (\Throwable $exception) {
                        throw new RuntimeException(
                            sprintf(
                                "Failed to drop foreign key '%s' in '%s'. In '%s' migration. DB error: %s",
                                $referenceName,
                                $tableName,
                                \get_called_class(),
                                $exception->getMessage()
                            )
                        );
                    }
                }
            }
        }

        if (isset($definition['indexes']) && $tableExists) {
            $indexes = [];
            foreach ($definition['indexes'] as $tableIndex) {
                $indexes[$tableIndex->getName()] = $tableIndex;
            }

            $localIndexes = [];
            $actualIndexes = self::$connection->describeIndexes($tableName, $defaultSchema);
            /** @var ReferenceInterface $actualIndex */
            foreach ($actualIndexes as $actualIndex) {
                $localIndexes[$actualIndex->getName()] = $actualIndex->getColumns();
            }

            foreach ($definition['indexes'] as $tableIndex) {
                if (!isset($localIndexes[$tableIndex->getName()])) {
                    if ($tableIndex->getName() == 'PRIMARY') {
                        $this->addPrimaryKey($tableName, $tableSchema, $tableIndex);
                    } else {
                        $this->addIndex($tableName, $tableSchema, $tableIndex);
                    }
                } else {
                    $changed = false;
                    if (count($tableIndex->getColumns()) != count($localIndexes[$tableIndex->getName()])) {
                        $changed = true;
                    } else {
                        foreach ($tableIndex->getColumns() as $columnName) {
                            if (!in_array($columnName, $localIndexes[$tableIndex->getName()])) {
                                $changed = true;
                                break;
                            }
                        }
                    }

                    if ($changed) {
                        if ($tableIndex->getName() == 'PRIMARY') {
                            $this->dropPrimaryKey($tableName, $tableSchema);
                            $this->addPrimaryKey($tableName, $tableSchema, $tableIndex);
                        } else {
                            $this->dropIndex($tableName, $tableSchema, $tableIndex->getName());
                            $this->addIndex($tableName, $tableSchema, $tableIndex);
                        }
                    }
                }
            }

            foreach ($localIndexes as $indexName => $indexColumns) {
                /**
                 * Skip existing keys
                 */
                if (isset($indexes[$indexName])) {
                    continue;
                }

                /**
                 * TODO: Check, why schemaName is empty string.
                 */
                $this->dropIndex($tableName, '', $indexName);
            }
        }
    }

    /**
     * Inserts data from a data migration file in a table
     *
     * @param string $tableName
     * @param mixed $fields
     * @param int $size Insert batch size
     */
    public function batchInsert(string $tableName, $fields, int $size = 1024): void
    {
        $migrationData = self::$migrationPath . $this->version . '/' . $tableName . '.dat';
        if (!file_exists($migrationData)) {
            return;
        }

        self::$connection->begin();

        $str = '';
        $pointer = 1;
        $batchHandler = fopen($migrationData, 'r');
        while (($line = fgetcsv($batchHandler)) !== false) {
            $values = array_map(
                function ($value) {
                    if (null === $value || $value === 'NULL') {
                        return 'NULL';
                    }

                    return self::$connection->escapeString(stripslashes($value));
                },
                $line
            );

            $str .= sprintf('(%s),', implode(',', $values));
            if ($pointer === $size) {
                $this->executeMultiInsert($tableName, $fields, $str);

                unset($str);
                $str = '';
                $pointer = 1;
            } else {
                $pointer++;
            }

            unset($line, $values);
        }

        if (!empty($str)) {
            $this->executeMultiInsert($tableName, $fields, $str);
            unset($str);
        }

        fclose($batchHandler);
        self::$connection->commit();
    }

    /**
     * Delete the migration datasets from the table
     *
     * @param string $tableName
     */
    public function batchDelete(string $tableName)
    {
        $migrationData = self::$migrationPath . $this->version . '/' . $tableName . '.dat';
        if (!file_exists($migrationData)) {
            return; // nothing to do
        }

        self::$connection->begin();
        self::$connection->delete($tableName);

        $batchHandler = fopen($migrationData, 'r');
        while (($line = fgetcsv($batchHandler)) !== false) {
            $values = array_map(
                function ($value) {
                    return null === $value ? null : stripslashes($value);
                },
                $line
            );

            self::$connection->delete($tableName, 'id = ?', [$values[0]]);
            unset($line);
        }

        fclose($batchHandler);
        self::$connection->commit();
    }

    /**
     * Get db connection
     *
     * @return AbstractAdapter
     */
    public function getConnection()
    {
        return self::$connection;
    }

    /**
     * Execute Multi Insert
     *
     * @param string $table
     * @param array $columns
     * @param string $values
     */
    protected function executeMultiInsert(string $table, array $columns, string $values): void
    {
        $query = sprintf(
            "INSERT INTO %s (%s) VALUES %s",
            $table,
            sprintf('%s', implode(',', $columns)),
            rtrim($values, ',') . ';'
        );

        self::$connection->execute($query);
        unset($query);
    }

    /**
     * Resolves the DB Schema
     *
     * @param Config $config
     * @return null|string
     */
    public static function resolveDbSchema(Config $config): ?string
    {
        if ($config->offsetExists('schema')) {
            return $config->get('schema');
        }

        $adapter = strtolower($config->get('adapter'));
        if (self::DB_ADAPTER_POSTGRESQL == $adapter) {
            return 'public';
        }

        if (self::DB_ADAPTER_SQLITE == $adapter) {
            // SQLite only supports the current database, unless one is
            // attached. This is not the case, so don't return a schema.
            return null;
        }

        if ($config->offsetExists('dbname')) {
            return $config->get('dbname');
        }

        return null;
    }

    /**
     * @param string $tableName
     * @param string $schemaName
     * @param IndexInterface $index
     * @throw RuntimeException
     */
    private function addPrimaryKey(string $tableName, string $schemaName, IndexInterface $index): void
    {
        try {
            self::$connection->addPrimaryKey($tableName, $schemaName, $index);
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    "Failed to add primary key '%s' in '%s'. In '%s' migration. DB error: %s",
                    $index->getName(),
                    $tableName,
                    \get_called_class(),
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @param string $tableName
     * @param string $schemaName
     * @throw RuntimeException
     */
    private function dropPrimaryKey(string $tableName, string $schemaName): void
    {
        try {
            self::$connection->dropPrimaryKey($tableName, $schemaName);
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    "Failed to drop primary key in '%s'. In '%s' migration. DB error: %s",
                    $tableName,
                    \get_called_class(),
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @param string $tableName
     * @param string $schemaName
     * @param IndexInterface $indexName
     * @throw RuntimeException
     */
    private function addIndex(string $tableName, string $schemaName, IndexInterface $indexName): void
    {
        try {
            self::$connection->addIndex($tableName, $schemaName, $indexName);
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    "Failed to add index '%s' in '%s'. In '%s' migration. DB error: %s",
                    $indexName->getName(),
                    $tableName,
                    \get_called_class(),
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @param string $tableName
     * @param string $schemaName
     * @param string $indexName
     * @throw RuntimeException
     */
    private function dropIndex(string $tableName, string $schemaName, string $indexName): void
    {
        try {
            self::$connection->dropIndex($tableName, $schemaName, $indexName);
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    "Failed to drop index '%s' in '%s'. In '%s' migration. DB error: %s",
                    $indexName,
                    $tableName,
                    \get_called_class(),
                    $exception->getMessage()
                )
            );
        }
    }
}
