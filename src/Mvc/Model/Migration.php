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
use Phalcon\Db\Column;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\Enum;
use Phalcon\Db\Exception as DbException;
use Phalcon\Db\Index;
use Phalcon\Db\ReferenceInterface;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Migrations\Db\Adapter\Pdo\PdoPostgresql;
use Phalcon\Migrations\Db\Dialect\DialectMysql;
use Phalcon\Migrations\Db\Dialect\DialectPostgresql;
use Phalcon\Migrations\Exception\Db\UnknownColumnTypeException;
use Phalcon\Migrations\Generator\Snippet;
use Phalcon\Migrations\Listeners\DbProfilerListener;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Utils;
use Phalcon\Migrations\Utils\Nullify;
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
    private static $migrationPath = null;

    /**
     * Skip auto increment
     *
     * @var bool
     */
    private static $skipAI = false;

    /**
     * Version of the migration file
     *
     * @var string
     */
    protected $version = null;

    /**
     * Prepares component
     *
     * @param Config $database Database config
     * @param bool $verbose array with settings
     * @throws DbException
     * @since 3.2.1 Using Postgresql::describeReferences and DialectPostgresql dialect class
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
        self::$connection = new $adapter($configArray);
        self::$databaseConfig = $database;

        // Connection custom dialect Dialect/DialectMysql
        if ($database->adapter == 'Mysql') {
            self::$connection->setDialect(new DialectMysql());
        }

        // Connection custom dialect Dialect/DialectPostgresql
        if ($database->adapter == 'Postgresql') {
            self::$connection->setDialect(new DialectPostgresql());
        }

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
     * @param array $exportDataFromTables
     * @return array
     * @throws DbException
     */
    public static function generateAll(
        ItemInterface $version,
        string $exportData = null,
        array $exportDataFromTables = []
    ): array {
        $classDefinition = [];
        $schema = Utils::resolveDbSchema(self::$databaseConfig);

        foreach (self::$connection->listTables($schema) as $table) {
            $classDefinition[$table] = self::generate($version, $table, $exportData, $exportDataFromTables);
        }

        return $classDefinition;
    }

    /**
     * Generate specified table migration
     *
     * @param ItemInterface $version
     * @param string $table
     * @param mixed $exportData
     * @param array $exportDataFromTables
     * @return string
     * @throws UnknownColumnTypeException
     */
    public static function generate(
        ItemInterface $version,
        string $table,
        $exportData = null,
        array $exportDataFromTables = []
    ): string {
        $oldColumn = null;
        $allFields = [];
        $numericFields = [];
        $tableDefinition = [];
        $snippet = new Snippet();

        $primaryColumn = null;
        $defaultSchema = Utils::resolveDbSchema(self::$databaseConfig);
        $description = self::$connection->describeColumns($table, $defaultSchema);
        $adapter = self::$databaseConfig->path('adapter');

        foreach ($description as $field) {
            /** @var ColumnInterface $field */
            $fieldDefinition = [];
            switch ($field->getType()) {
                case Column::TYPE_BIGINTEGER:
                    $fieldDefinition[] = "'type' => Column::TYPE_BIGINTEGER";
                    break;
                case Column::TYPE_INTEGER:
                    $fieldDefinition[] = "'type' => Column::TYPE_INTEGER";
                    $numericFields[$field->getName()] = true;
                    break;
                case Column::TYPE_MEDIUMINTEGER:
                    $fieldDefinition[] = "'type' => Column::TYPE_MEDIUMINTEGER";
                    $numericFields[$field->getName()] = true;
                    break;
                case Column::TYPE_SMALLINTEGER:
                    $fieldDefinition[] = "'type' => Column::TYPE_SMALLINTEGER";
                    $numericFields[$field->getName()] = true;
                    break;
                case Column::TYPE_TINYINTEGER:
                    $fieldDefinition[] = "'type' => Column::TYPE_TINYINTEGER";
                    $numericFields[$field->getName()] = true;
                    break;
                case Column::TYPE_VARCHAR:
                    $fieldDefinition[] = "'type' => Column::TYPE_VARCHAR";
                    break;
                case Column::TYPE_CHAR:
                    $fieldDefinition[] = "'type' => Column::TYPE_CHAR";
                    break;
                case Column::TYPE_TIME:
                    $fieldDefinition[] = "'type' => Column::TYPE_TIME";
                    break;
                case Column::TYPE_DATE:
                    $fieldDefinition[] = "'type' => Column::TYPE_DATE";
                    break;
                case Column::TYPE_DATETIME:
                    $fieldDefinition[] = "'type' => Column::TYPE_DATETIME";
                    break;
                case Column::TYPE_TIMESTAMP:
                    $fieldDefinition[] = "'type' => Column::TYPE_TIMESTAMP";
                    break;
                case Column::TYPE_DECIMAL:
                    $fieldDefinition[] = "'type' => Column::TYPE_DECIMAL";
                    $numericFields[$field->getName()] = true;
                    break;
                case Column::TYPE_TEXT:
                    $fieldDefinition[] = "'type' => Column::TYPE_TEXT";
                    break;
                case Column::TYPE_MEDIUMTEXT:
                    $fieldDefinition[] = "'type' => Column::TYPE_MEDIUMTEXT";
                    break;
                case Column::TYPE_LONGTEXT:
                    $fieldDefinition[] = "'type' => Column::TYPE_LONGTEXT";
                    break;
                case Column::TYPE_TINYTEXT:
                    $fieldDefinition[] = "'type' => Column::TYPE_TINYTEXT";
                    break;
                case Column::TYPE_BOOLEAN:
                    $fieldDefinition[] = "'type' => Column::TYPE_BOOLEAN";
                    break;
                case Column::TYPE_FLOAT:
                    $fieldDefinition[] = "'type' => Column::TYPE_FLOAT";
                    break;
                case Column::TYPE_DOUBLE:
                    $fieldDefinition[] = "'type' => Column::TYPE_DOUBLE";
                    break;
                case Column::TYPE_TINYBLOB:
                    $fieldDefinition[] = "'type' => Column::TYPE_TINYBLOB";
                    break;
                case Column::TYPE_BLOB:
                    $fieldDefinition[] = "'type' => Column::TYPE_BLOB";
                    break;
                case Column::TYPE_MEDIUMBLOB:
                    $fieldDefinition[] = "'type' => Column::TYPE_MEDIUMBLOB";
                    break;
                case Column::TYPE_LONGBLOB:
                    $fieldDefinition[] = "'type' => Column::TYPE_LONGBLOB";
                    break;
                case Column::TYPE_JSON:
                    $fieldDefinition[] = "'type' => Column::TYPE_JSON";
                    break;
                case Column::TYPE_JSONB:
                    $fieldDefinition[] = "'type' => Column::TYPE_JSONB";
                    break;
                case Column::TYPE_ENUM:
                    $fieldDefinition[] = "'type' => Column::TYPE_ENUM";
                    break;
                default:
                    throw new UnknownColumnTypeException($field);
            }

            if ($field->hasDefault() && !$field->isAutoIncrement()) {
                $default = $field->getDefault();
                $fieldDefinition[] = "'default' => \"$default\"";
            }

            if ($field->isPrimary() && $adapter == 'postgresql') {
                $fieldDefinition[] = "'primary' => true";
                $primaryColumn = $field->getName();
            }

            if ($field->isUnsigned()) {
                $fieldDefinition[] = "'unsigned' => true";
            }

            if ($field->isNotNull()) {
                $fieldDefinition[] = "'notNull' => true";
            } elseif (!$field->isPrimary()) {
                // A primary key column cannot have NULL values.
                $fieldDefinition[] = "'notNull' => false";
            }

            if ($field->isAutoIncrement()) {
                $fieldDefinition[] = "'autoIncrement' => true";
            }

            $noSizeTypes = [
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

            if (
                $adapter == 'postgresql' &&
                in_array($field->getType(), [Column::TYPE_BOOLEAN, Column::TYPE_INTEGER, Column::TYPE_BIGINTEGER])
            ) {
                // nothing
            } else {
                if ($field->getSize()) {
                    if ($field->getType() === Column::TYPE_ENUM) {
                        $fieldDefinition[] = "'size' => \"" . $field->getSize() . "\"";
                    } else {
                        $fieldDefinition[] = "'size' => " . $field->getSize();
                    }
                } elseif (!in_array($field->getType(), $noSizeTypes)) {
                    $fieldDefinition[] = "'size' => 1";
                }
            }

            if ($field->getScale()) {
                $fieldDefinition[] = "'scale' => " . $field->getScale();
            }

            if ($oldColumn != null) {
                $fieldDefinition[] = "'after' => '" . $oldColumn . "'";
            } else {
                $fieldDefinition[] = "'first' => true";
            }

            $oldColumn = $field->getName();
            $tableDefinition[] = $snippet->getColumnDefinition($field->getName(), $fieldDefinition);
            $allFields[] = "'" . $field->getName() . "'";
        }

        $indexesDefinition = [];
        $indexes = self::$connection->describeIndexes($table, $defaultSchema);
        foreach ($indexes as $indexName => $dbIndex) {
            /** @var Index $dbIndex */
            $indexDefinition = [];
            foreach ($dbIndex->getColumns() as $indexColumn) {
                // [PGSQL] Skip primary key column
                if ($indexColumn !== $primaryColumn) {
                    $indexDefinition[] = "'" . $indexColumn . "'";
                }
            }

            if (!empty($indexDefinition)) {
                $indexesDefinition[] = $snippet->getIndexDefinition($indexName, $indexDefinition, $dbIndex->getType());
            }
        }

        $referencesDefinition = [];
        $references = self::$connection->describeReferences($table, $defaultSchema);
        foreach ($references as $constraintName => $dbReference) {
            $columns = [];
            foreach ($dbReference->getColumns() as $column) {
                $columns[] = "'" . $column . "'";
            }

            $referencedColumns = [];
            foreach ($dbReference->getReferencedColumns() as $referencedColumn) {
                $referencedColumns[] = "'" . $referencedColumn . "'";
            }

            $referenceDefinition = [];
            $referenceDefinition[] = "'referencedTable' => '" . $dbReference->getReferencedTable() . "'";
            $referenceDefinition[] = "'referencedSchema' => '" . $dbReference->getReferencedSchema() . "'";
            $referenceDefinition[] = "'columns' => [" . join(",", array_unique($columns)) . "]";
            $referenceDefinition[] = "'referencedColumns' => [" . join(",", array_unique($referencedColumns)) . "]";
            $referenceDefinition[] = "'onUpdate' => '" . $dbReference->getOnUpdate() . "'";
            $referenceDefinition[] = "'onDelete' => '" . $dbReference->getOnDelete() . "'";

            $referencesDefinition[] = $snippet->getReferenceDefinition($constraintName, $referenceDefinition);
        }

        $optionsDefinition = [];
        $tableOptions = self::$connection->tableOptions($table, $defaultSchema);
        foreach ($tableOptions as $optionName => $optionValue) {
            if (self::$skipAI && strtoupper($optionName) == "AUTO_INCREMENT") {
                $optionValue = '';
            }

            $optionsDefinition[] = "'" . strtoupper($optionName) . "' => '" . $optionValue . "'";
        }

        $classVersion = preg_replace('/[^0-9A-Za-z]/', '', (string)$version->getStamp());
        $className = Text::camelize($table) . 'Migration_' . $classVersion;

        // morph()
        $classData = $snippet->getMigrationMorph($className, $table, $tableDefinition);

        if (count($indexesDefinition)) {
            $classData .= $snippet->getMigrationDefinition('indexes', $indexesDefinition);
        }

        if (count($referencesDefinition)) {
            $classData .= $snippet->getMigrationDefinition('references', $referencesDefinition);
        }

        if (count($optionsDefinition)) {
            $classData .= $snippet->getMigrationDefinition('options', $optionsDefinition);
        }

        $classData .= "            ]\n        );\n    }\n";

        // up()
        $classData .= $snippet->getMigrationUp();

        if ($exportData == 'always' || self::shouldExportDataFromTable($table, $exportDataFromTables)) {
            $classData .= $snippet->getMigrationBatchInsert($table, $allFields);
        }

        $classData .= "\n    }\n";

        // down()
        $classData .= $snippet->getMigrationDown();

        if ($exportData == 'always' || self::shouldExportDataFromTable($table, $exportDataFromTables)) {
            $classData .= $snippet->getMigrationBatchDelete($table);
        }

        $classData .= "\n    }\n";

        // afterCreateTable()
        if ($exportData == 'oncreate' || self::shouldExportDataFromTable($table, $exportDataFromTables)) {
            $classData .= $snippet->getMigrationAfterCreateTable($table, $allFields);
        }

        // end of class
        $classData .= "\n}\n";

        // dump data
        if (
            $exportData == 'always' ||
            $exportData == 'oncreate' ||
            self::shouldExportDataFromTable($table, $exportDataFromTables)
        ) {
            $fileHandler = fopen(self::$migrationPath . $version->getVersion() . '/' . $table . '.dat', 'w');
            $cursor = self::$connection->query('SELECT * FROM ' . self::$connection->escapeIdentifier($table));
            $cursor->setFetchMode(Enum::FETCH_ASSOC);
            while ($row = $cursor->fetchArray()) {
                $data = [];
                foreach ($row as $key => $value) {
                    if (isset($numericFields[$key])) {
                        if ($value === '' || is_null($value)) {
                            $data[] = 'NULL';
                        } else {
                            $data[] = addslashes($value);
                        }
                    } else {
                        $data[] = is_null($value) ? "NULL" : addslashes($value);
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

    public static function shouldExportDataFromTable(string $table, array $exportDataFromTables): bool
    {
        return in_array($table, $exportDataFromTables);
    }

    /**
     * Migrate
     *
     * @param string $tableName
     * @param ItemInterface|null $fromVersion
     * @param ItemInterface|null $toVersion
     * @throws Exception
     */
    public static function migrate(
        string $tableName,
        ItemInterface $fromVersion = null,
        ItemInterface $toVersion = null
    ): void {
        $fromVersion = $fromVersion ?: VersionCollection::createItem($fromVersion);
        $toVersion = $toVersion ?: VersionCollection::createItem($toVersion);

        if ($fromVersion->getStamp() == $toVersion->getStamp()) {
            return; // nothing to do
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

        $migration = new $className($version);
        $migration->version = $version;

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
    private static function createPrevClassWithMorphMethod(ItemInterface $toVersion, $tableName): ?Migration
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
        $defaultSchema = Utils::resolveDbSchema(self::$databaseConfig);
        $tableExists = self::$connection->tableExists($tableName, $defaultSchema);
        $tableSchema = $defaultSchema;

        if (isset($definition['columns'])) {
            if (count($definition['columns']) == 0) {
                throw new DbException('Table must have at least one column');
            }

            $fields = [];
            /** @var ColumnInterface $tableColumn */
            foreach ($definition['columns'] as $tableColumn) {
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
                        self::$connection->addColumn($tableName, $tableSchema, $column);
                    } else {
                        $changed = false;

                        if ($localFields[$fieldName]->getType() != $column->getType()) {
                            $changed = true;
                        }

                        if ($localFields[$fieldName]->getSize() != $column->getSize()) {
                            $changed = true;
                        }

                        if ($column->isNotNull() != $localFields[$fieldName]->isNotNull()) {
                            $changed = true;
                        }

                        if ($column->getDefault() != $localFields[$fieldName]->getDefault()) {
                            $changed = true;
                        }

                        if ($changed) {
                            self::$connection->modifyColumn($tableName, $tableSchema, $column, $column);
                        }
                    }
                }

                foreach ($localFields as $fieldName => $localField) {
                    if (!isset($fields[$fieldName])) {
                        self::$connection->dropColumn($tableName, '', $fieldName);
                    }
                }
            } else {
                self::$connection->createTable($tableName, $defaultSchema, $definition);
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
                    'referencedTable' => $activeReference->getReferencedTable(),
                    'referencedSchema' => $activeReference->getReferencedSchema(),
                    'columns' => $activeReference->getColumns(),
                    'referencedColumns' => $activeReference->getReferencedColumns(),
                ];
            }

            foreach ($definition['references'] as $tableReference) {
                $schemaName = $tableReference->getSchemaName() ?? '';

                if (!isset($localReferences[$tableReference->getName()])) {
                    self::$connection->addForeignKey(
                        $tableName,
                        $schemaName,
                        $tableReference
                    );

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
                    self::$connection->dropForeignKey(
                        $tableName,
                        $schemaName,
                        $tableReference->getName()
                    );
                    self::$connection->addForeignKey(
                        $tableName,
                        $schemaName,
                        $tableReference
                    );
                }
            }

            foreach ($localReferences as $referenceName => $reference) {
                if (!isset($references[$referenceName])) {
                    self::$connection->dropForeignKey($tableName, '', $referenceName);
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
                        self::$connection->addPrimaryKey($tableName, $tableSchema, $tableIndex);
                    } else {
                        self::$connection->addIndex($tableName, $tableSchema, $tableIndex);
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
                            self::$connection->dropPrimaryKey($tableName, $tableSchema);
                            self::$connection->addPrimaryKey($tableName, $tableSchema, $tableIndex);
                        } else {
                            self::$connection->dropIndex($tableName, $tableSchema, $tableIndex->getName());
                            self::$connection->addIndex($tableName, $tableSchema, $tableIndex);
                        }
                    }
                }
            }

            foreach ($localIndexes as $indexName => $indexColumns) {
                if (!isset($indexes[$indexName])) {
                    self::$connection->dropIndex($tableName, '', $indexName);
                }
            }
        }
    }

    /**
     * Inserts data from a data migration file in a table
     *
     * @param string $tableName
     * @param mixed $fields
     */
    public function batchInsert(string $tableName, $fields)
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

            $nullify = new Nullify();
            self::$connection->insert($tableName, $nullify($values), $fields);
            unset($line);
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
}
