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
use Nette\PhpGenerator\PsrPrinter;
use Phalcon\Migrations\Db\Adapter\AdapterFactory;
use Phalcon\Migrations\Db\Adapter\AdapterInterface;
use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Db\Connection;
use Phalcon\Migrations\Db\PhalconColumnBridge;
use Phalcon\Migrations\Listeners\DbProfilerListener;
use Phalcon\Migrations\Db\FieldDefinition;
use Phalcon\Migrations\Db\Index;
use Phalcon\Migrations\Db\Reference;
use Phalcon\Migrations\Exception\Db\UnknownColumnTypeException;
use Phalcon\Migrations\Exception\RuntimeException;
use Phalcon\Migrations\Generator\Snippet;
use Phalcon\Migrations\Migration\Action\Generate as GenerateAction;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Utils\Config;
use Phalcon\Migrations\Version\ItemCollection as VersionCollection;
use Phalcon\Migrations\Version\ItemInterface;
use Phalcon\Support\Helper\Str\Camelize;
use Throwable;

use function array_map;
use function class_exists;
use function fclose;
use function fgetcsv;
use function file_exists;
use function fopen;
use function get_called_class;
use function implode;
use function in_array;
use function method_exists;
use function preg_replace;
use function rtrim;
use function sprintf;
use function stripslashes;
use function strtolower;

use const DIRECTORY_SEPARATOR;

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
    public const DIRECTION_BACK    = -1;

    public const DB_ADAPTER_MYSQL      = 'mysql';
    public const DB_ADAPTER_POSTGRESQL = 'postgresql';
    public const DB_ADAPTER_SQLITE     = 'sqlite';

    private static AdapterInterface $adapter;

    /**
     * @var Config
     */
    private static Config $databaseConfig;

    private static string $migrationPath = '';

    private static bool $skipAI = true;

    protected ?string $version = null;

    /**
     * Prepares component
     *
     * @param Config $config  Database config
     * @param bool   $verbose array with settings
     */
    public static function setup(Config $config, bool $verbose = false): void
    {
        if ($config->adapter === null) {
            throw new RuntimeException('Unspecified database Adapter in your configuration!');
        }

        $connection = Connection::fromConfig($config);

        if ($verbose && Migrations::isConsole()) {
            (new DbProfilerListener())->attach($connection);
        }

        self::$adapter        = AdapterFactory::create($connection);
        self::$databaseConfig = $config;
    }

    public static function setSkipAutoIncrement(bool $skip): void
    {
        self::$skipAI = $skip;
    }

    public static function setMigrationPath(string $path): void
    {
        self::$migrationPath = rtrim($path, '\\/') . DIRECTORY_SEPARATOR;
    }

    public static function getAdapter(): AdapterInterface
    {
        return self::$adapter;
    }

    public static function getSchema(): ?string
    {
        return self::resolveDbSchema(self::$databaseConfig);
    }

    /**
     * @throws UnknownColumnTypeException
     */
    public static function generateAll(
        ItemInterface $version,
        ?string $exportData = null,
        array $exportTables = [],
        bool $skipRefSchema = false
    ): array {
        $classDefinition = [];
        $schema          = self::resolveDbSchema(self::$databaseConfig);

        foreach (self::$adapter->listTables($schema ?? '') as $table) {
            $classDefinition[$table] = self::generate(
                $version,
                $table,
                $exportData,
                $exportTables,
                $skipRefSchema
            );
        }

        return $classDefinition;
    }

    /**
     * @throws UnknownColumnTypeException
     */
    public static function generate(
        ItemInterface $version,
        string $table,
        $exportData = null,
        array $exportTables = [],
        bool $skipRefSchema = false
    ): string {
        $camelize      = new Camelize();
        $printer       = new PsrPrinter();
        $snippet       = new Snippet();
        $adapter       = strtolower((string) self::$databaseConfig->adapter);
        $defaultSchema = self::resolveDbSchema(self::$databaseConfig);

        $columns    = self::$adapter->listColumns($defaultSchema ?? '', $table);
        $indexes    = self::$adapter->listIndexes($defaultSchema ?? '', $table);
        $references = self::$adapter->listReferences($defaultSchema ?? '', $table);
        $options    = self::$adapter->getTableOptions($defaultSchema ?? '', $table);

        $classVersion = preg_replace('/[^0-9A-Za-z]/', '', (string) $version->getStamp());
        $className    = $camelize->__invoke($table) . 'Migration_' . $classVersion;

        $shouldExportDataFromTable = in_array($table, $exportTables, true);

        $generateAction = new GenerateAction(
            $adapter,
            $columns,
            $indexes,
            $references,
            $options
        );

        $generateAction->createEntity($className)
                       ->addMorph($snippet, $table, $skipRefSchema, self::$skipAI)
                       ->addUp($table, $exportData, $shouldExportDataFromTable)
                       ->addDown($table, $exportData, $shouldExportDataFromTable)
                       ->addAfterCreateTable($table, $exportData)
                       ->createDumpFiles(
                           $table,
                           self::$migrationPath,
                           self::$adapter,
                           $version,
                           $exportData,
                           $shouldExportDataFromTable
                       );

        return $printer->printFile($generateAction->getEntity());
    }

    /**
     * @throws Exception
     */
    public static function migrate(
        string $tableName,
        ?ItemInterface $fromVersion = null,
        ?ItemInterface $toVersion = null,
        bool $skipForeignChecks = false
    ): void {
        $fromVersion = $fromVersion ?: VersionCollection::createItem($fromVersion);
        $toVersion   = $toVersion ?: VersionCollection::createItem($toVersion);

        if ($fromVersion->getStamp() === $toVersion->getStamp()) {
            return;
        }

        $driver = strtolower(self::$adapter->getConnection()->getDriverName());

        if ($skipForeignChecks) {
            if ($driver === 'mysql') {
                self::$adapter->execute('SET FOREIGN_KEY_CHECKS=0');
            } elseif ($driver === 'pgsql') {
                self::$adapter->execute("SET session_replication_role = 'replica'");
            }
        }

        if ($fromVersion->getStamp() < $toVersion->getStamp()) {
            $toMigration = self::createClass($toVersion, $tableName);

            if ($toMigration !== null) {
                if (method_exists($toMigration, 'morph')) {
                    $toMigration->morph();
                }

                if (method_exists($toMigration, 'up')) {
                    $toMigration->up();
                    if (method_exists($toMigration, 'afterUp')) {
                        $toMigration->afterUp();
                    }
                }
            }
        } else {
            $fromMigration = self::createClass($fromVersion, $tableName);
            if ($fromMigration !== null && method_exists($fromMigration, 'down')) {
                $fromMigration->down();
                if (method_exists($fromMigration, 'afterDown')) {
                    $fromMigration->afterDown();
                }
            }

            $toMigration = self::createPrevClassWithMorphMethod($toVersion, $tableName);
            if ($toMigration !== null && method_exists($toMigration, 'morph')) {
                $toMigration->morph();
            }
        }

        if ($skipForeignChecks) {
            if ($driver === 'mysql') {
                self::$adapter->execute('SET FOREIGN_KEY_CHECKS=1');
            } elseif ($driver === 'pgsql') {
                self::$adapter->execute("SET session_replication_role = 'origin'");
            }
        }
    }

    /**
     * @throws Exception
     */
    private static function createClass(ItemInterface $version, string $tableName): ?Migration
    {
        $camelize = new Camelize();
        $fileName = self::$migrationPath . $version->getVersion() . DIRECTORY_SEPARATOR . $tableName . '.php';
        if (!file_exists($fileName)) {
            return null;
        }

        $className = $camelize->__invoke($tableName) . 'Migration_' . $version->getStamp();

        include_once $fileName;
        if (!class_exists($className)) {
            throw new Exception('Migration class cannot be found ' . $className . ' at ' . $fileName);
        }

        /** @var Migration $migration */
        $migration          = new $className($version);
        $migration->version = $version->__toString();

        return $migration;
    }

    /**
     * @throws Exception
     */
    private static function createPrevClassWithMorphMethod(
        ItemInterface $toVersion,
        string $tableName
    ): ?Migration {
        $prevVersions = [];
        $versions     = self::scanForVersions(self::$migrationPath);
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

    public function morphTable(string $tableName, array $definition): void
    {
        $schema      = self::resolveDbSchema(self::$databaseConfig);
        $tableExists = self::$adapter->tableExists($tableName, $schema ?? '');
        $tableSchema = (string) $schema;

        if (isset($definition['columns'])) {
            if (count($definition['columns']) === 0) {
                throw new RuntimeException('Table must have at least one column');
            }

            $fields        = [];
            $previousField = null;
            foreach ($definition['columns'] as $tableColumn) {
                if (!$tableColumn instanceof Column) {
                    // Transparently convert Phalcon\Db\Column objects from pre-upgrade migration files
                    if (is_object($tableColumn) && method_exists($tableColumn, 'getType')) {
                        $tableColumn = PhalconColumnBridge::fromPhalcon($tableColumn);
                    } else {
                        throw new RuntimeException('Table must have at least one column');
                    }
                }

                $field = new FieldDefinition($tableColumn);
                $field->setPrevious($previousField);
                if ($previousField !== null) {
                    $previousField->setNext($field);
                }

                $previousField                     = $field;
                $fields[$field->getName()]         = $field;
            }

            if ($tableExists) {
                $localFields   = [];
                $previousField = null;
                foreach (self::$adapter->listColumns($schema ?? '', $tableName) as $localColumn) {
                    $field = new FieldDefinition($localColumn);
                    $field->setPrevious($previousField);
                    if ($previousField !== null) {
                        $previousField->setNext($field);
                    }

                    $previousField                 = $field;
                    $localFields[$field->getName()] = $field;
                }

                foreach ($fields as $fieldDefinition) {
                    $localFieldDefinition = $fieldDefinition->getPairedDefinition($localFields);
                    if ($localFieldDefinition === null) {
                        try {
                            self::$adapter->addColumn(
                                $tableName,
                                $tableSchema,
                                $fieldDefinition->getColumn()
                            );
                        } catch (Throwable $e) {
                            throw new RuntimeException(sprintf(
                                "Failed to add column '%s' in table '%s'. In '%s' migration. DB error: %s",
                                $fieldDefinition->getName(),
                                $tableName,
                                get_called_class(),
                                $e->getMessage()
                            ));
                        }

                        continue;
                    }

                    if ($fieldDefinition->isChanged($localFieldDefinition)) {
                        try {
                            self::$adapter->modifyColumn(
                                $tableName,
                                $tableSchema,
                                $fieldDefinition->getColumn(),
                                $localFieldDefinition->getColumn()
                            );
                        } catch (Throwable $e) {
                            throw new RuntimeException(sprintf(
                                "Failed to modify column '%s' in table '%s'. In '%s' migration. DB error: %s",
                                $fieldDefinition->getName(),
                                $tableName,
                                get_called_class(),
                                $e->getMessage()
                            ));
                        }
                    }
                }

                foreach ($localFields as $fieldDefinition) {
                    if ($fieldDefinition->getPairedDefinition($fields) === null) {
                        try {
                            self::$adapter->dropColumn($tableName, '', $fieldDefinition->getName());
                        } catch (Throwable $e) {
                            throw new RuntimeException(sprintf(
                                "Failed to drop column '%s' in table '%s'. In '%s' migration. DB error: %s",
                                $fieldDefinition->getName(),
                                $tableName,
                                get_called_class(),
                                $e->getMessage()
                            ));
                        }
                    }
                }
            } else {
                try {
                    self::$adapter->createTable($tableName, $tableSchema, $definition);
                } catch (Throwable $e) {
                    throw new RuntimeException(sprintf(
                        "Failed to create table '%s'. In '%s' migration. DB error: %s",
                        $tableName,
                        get_called_class(),
                        $e->getMessage()
                    ));
                }

                if (method_exists($this, 'afterCreateTable')) {
                    $this->afterCreateTable();
                }
            }
        }

        if (isset($definition['references']) && $tableExists) {
            $references = [];
            foreach ($definition['references'] as $ref) {
                $references[$ref->getName()] = $ref;
            }

            $localReferences  = [];
            foreach (self::$adapter->listReferences($schema ?? '', $tableName) as $activeRef) {
                $localReferences[$activeRef->getName()] = [
                    'columns'           => $activeRef->getColumns(),
                    'referencedTable'   => $activeRef->getReferencedTable(),
                    'referencedSchema'  => $activeRef->getReferencedSchema(),
                    'referencedColumns' => $activeRef->getReferencedColumns(),
                ];
            }

            foreach ($definition['references'] as $tableReference) {
                $schemaName = $tableReference->getReferencedSchema() ?? '';

                if (!isset($localReferences[$tableReference->getName()])) {
                    try {
                        self::$adapter->addForeignKey($tableName, $schemaName, $tableReference);
                    } catch (Throwable $e) {
                        throw new RuntimeException(sprintf(
                            "Failed to add foreign key '%s' in '%s'. In '%s' migration. DB error: %s",
                            $tableReference->getName(),
                            $tableName,
                            get_called_class(),
                            $e->getMessage()
                        ));
                    }

                    continue;
                }

                $local   = $localReferences[$tableReference->getName()];
                $changed = $tableReference->getReferencedTable() !== $local['referencedTable']
                    || count($tableReference->getColumns()) !== count($local['columns'])
                    || count($tableReference->getReferencedColumns()) !== count($local['referencedColumns']);

                if (!$changed) {
                    foreach ($tableReference->getColumns() as $col) {
                        if (!in_array($col, $local['columns'], true)) {
                            $changed = true;
                            break;
                        }
                    }
                }

                if (!$changed) {
                    foreach ($tableReference->getReferencedColumns() as $col) {
                        if (!in_array($col, $local['referencedColumns'], true)) {
                            $changed = true;
                            break;
                        }
                    }
                }

                if ($changed) {
                    try {
                        self::$adapter->dropForeignKey($tableName, $schemaName, $tableReference->getName());
                    } catch (Throwable $e) {
                        throw new RuntimeException(sprintf(
                            "Failed to drop foreign key '%s' in '%s'. In '%s' migration. DB error: %s",
                            $tableReference->getName(),
                            $tableName,
                            get_called_class(),
                            $e->getMessage()
                        ));
                    }

                    try {
                        self::$adapter->addForeignKey($tableName, $schemaName, $tableReference);
                    } catch (Throwable $e) {
                        throw new RuntimeException(sprintf(
                            "Failed to add foreign key '%s' in '%s'. In '%s' migration. DB error: %s",
                            $tableReference->getName(),
                            $tableName,
                            get_called_class(),
                            $e->getMessage()
                        ));
                    }
                }
            }

            foreach ($localReferences as $refName => $reference) {
                if (!isset($references[$refName])) {
                    try {
                        self::$adapter->dropForeignKey($tableName, '', $refName);
                    } catch (Throwable $e) {
                        throw new RuntimeException(sprintf(
                            "Failed to drop foreign key '%s' in '%s'. In '%s' migration. DB error: %s",
                            $refName,
                            $tableName,
                            get_called_class(),
                            $e->getMessage()
                        ));
                    }
                }
            }
        }

        if (isset($definition['indexes']) && $tableExists) {
            $indexes = [];
            foreach ($definition['indexes'] as $idx) {
                $indexes[$idx->getName()] = $idx;
            }

            $localIndexes = [];
            foreach (self::$adapter->listIndexes($schema ?? '', $tableName) as $actualIndex) {
                $localIndexes[$actualIndex->getName()] = $actualIndex->getColumns();
            }

            foreach ($definition['indexes'] as $tableIndex) {
                if (!isset($localIndexes[$tableIndex->getName()])) {
                    if (
                        $tableIndex->getName() === 'PRIMARY'
                        || $tableIndex->getType() === Index::TYPE_PRIMARY
                        || $tableIndex->getType() === Index::TYPE_PRIMARY_ALT
                    ) {
                        $this->addPrimaryKey($tableName, $tableSchema, $tableIndex);
                    } else {
                        $this->addIndex($tableName, $tableSchema, $tableIndex);
                    }
                } else {
                    $changed = count($tableIndex->getColumns()) !== count($localIndexes[$tableIndex->getName()]);
                    if (!$changed) {
                        foreach ($tableIndex->getColumns() as $col) {
                            if (!in_array($col, $localIndexes[$tableIndex->getName()], true)) {
                                $changed = true;
                                break;
                            }
                        }
                    }

                    if ($changed) {
                        if (
                            $tableIndex->getName() === 'PRIMARY'
                            || $tableIndex->getType() === Index::TYPE_PRIMARY
                            || $tableIndex->getType() === Index::TYPE_PRIMARY_ALT
                        ) {
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
                if (!isset($indexes[$indexName])) {
                    $this->dropIndex($tableName, '', $indexName);
                }
            }
        }
    }

    public function batchInsert(string $tableName, array $fields, int $size = 1024): void
    {
        $migrationData = self::$migrationPath . $this->version . '/' . $tableName . '.dat';
        if (!file_exists($migrationData)) {
            return;
        }

        $connection = self::$adapter;
        $connection->begin();

        $str          = '';
        $pointer      = 1;
        $batchHandler = fopen($migrationData, 'r');
        while (($line = fgetcsv($batchHandler)) !== false) {
            $values = array_map(
                static function ($value) use ($connection) {
                    if ($value === null || $value === 'NULL') {
                        return 'NULL';
                    }

                    return $connection->quote(stripslashes($value));
                },
                $line
            );

            $str .= sprintf('(%s),', implode(',', $values));
            if ($pointer === $size) {
                $this->executeMultiInsert($tableName, $fields, $str);
                $str     = '';
                $pointer = 1;
            } else {
                $pointer++;
            }

            unset($line, $values);
        }

        if ($str !== '') {
            $this->executeMultiInsert($tableName, $fields, $str);
        }

        fclose($batchHandler);
        $connection->commit();
    }

    public function batchDelete(string $tableName): void
    {
        $migrationData = self::$migrationPath . $this->version . '/' . $tableName . '.dat';
        if (!file_exists($migrationData)) {
            return;
        }

        $connection = self::$adapter;
        $connection->begin();
        $connection->execute("DELETE FROM {$tableName}");

        $batchHandler = fopen($migrationData, 'r');
        while (($line = fgetcsv($batchHandler)) !== false) {
            $values = array_map(
                static fn($v) => $v === null ? null : stripslashes($v),
                $line
            );

            $connection->execute(
                "DELETE FROM {$tableName} WHERE id = " . $connection->quote((string) $values[0])
            );

            unset($line);
        }

        fclose($batchHandler);
        $connection->commit();
    }

    public function getConnection(): AdapterInterface
    {
        return self::$adapter;
    }

    protected function executeMultiInsert(string $table, array $columns, string $values): void
    {
        $cols  = implode(',', $columns);
        $query = sprintf(
            "INSERT INTO %s (%s) VALUES %s",
            $table,
            $cols,
            rtrim($values, ',') . ';'
        );

        self::$adapter->execute($query);
    }

    public static function resolveDbSchema(Config $config): ?string
    {
        if ($config->schema !== null) {
            return $config->schema;
        }

        $adapter = strtolower((string) $config->adapter);
        if (self::DB_ADAPTER_POSTGRESQL === $adapter) {
            return 'public';
        }

        if (self::DB_ADAPTER_SQLITE === $adapter) {
            return null;
        }

        return $config->dbname;
    }

    private function addPrimaryKey(string $tableName, string $schemaName, Index $index): void
    {
        try {
            self::$adapter->addPrimaryKey($tableName, $schemaName, $index);
        } catch (Throwable $e) {
            throw new RuntimeException(sprintf(
                "Failed to add primary key '%s' in '%s'. In '%s' migration. DB error: %s",
                $index->getName(),
                $tableName,
                get_called_class(),
                $e->getMessage()
            ));
        }
    }

    private function dropPrimaryKey(string $tableName, string $schemaName): void
    {
        try {
            self::$adapter->dropPrimaryKey($tableName, $schemaName);
        } catch (Throwable $e) {
            throw new RuntimeException(sprintf(
                "Failed to drop primary key in '%s'. In '%s' migration. DB error: %s",
                $tableName,
                get_called_class(),
                $e->getMessage()
            ));
        }
    }

    private function addIndex(string $tableName, string $schemaName, Index $index): void
    {
        try {
            self::$adapter->addIndex($tableName, $schemaName, $index);
        } catch (Throwable $e) {
            throw new RuntimeException(sprintf(
                "Failed to add index '%s' in '%s'. In '%s' migration. DB error: %s",
                $index->getName(),
                $tableName,
                get_called_class(),
                $e->getMessage()
            ));
        }
    }

    private function dropIndex(string $tableName, string $schemaName, string $indexName): void
    {
        try {
            self::$adapter->dropIndex($tableName, $schemaName, $indexName);
        } catch (Throwable $e) {
            throw new RuntimeException(sprintf(
                "Failed to drop index '%s' in '%s'. In '%s' migration. DB error: %s",
                $indexName,
                $tableName,
                get_called_class(),
                $e->getMessage()
            ));
        }
    }
}
