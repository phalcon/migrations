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

namespace Phalcon\Migrations\Exception;

use function sprintf;

class RuntimeException extends \InvalidArgumentException
{
    public static function cannotLoadDatabaseConfiguration(): self
    {
        return new self('Cannot load database configuration');
    }

    public static function cannotWriteDirectory(string $path): self
    {
        return new self("Unable to write '{$path}' directory. Permission denied");
    }

    public static function cannotWriteFile(string $path): self
    {
        return new self("Unable to write '{$path}' file. Permission denied");
    }

    public static function configMustBeInstance(): self
    {
        return new self(
            'Internal error. Config should be an instance of Phalcon\\Migrations\\Utils\\Config'
        );
    }

    public static function directoryNotCreated(string $path): self
    {
        return new self(sprintf('Directory "%s" was not created', $path));
    }

    public static function failedToAddColumn(
        string $column,
        string $table,
        string $migrationClass,
        string $dbError
    ): self {
        return new self(sprintf(
            "Failed to add column '%s' in table '%s'. In '%s' migration. DB error: %s",
            $column,
            $table,
            $migrationClass,
            $dbError
        ));
    }

    public static function failedToAddForeignKey(
        string $key,
        string $table,
        string $migrationClass,
        string $dbError
    ): self {
        return new self(sprintf(
            "Failed to add foreign key '%s' in '%s'. In '%s' migration. DB error: %s",
            $key,
            $table,
            $migrationClass,
            $dbError
        ));
    }

    public static function failedToAddIndex(
        string $index,
        string $table,
        string $migrationClass,
        string $dbError
    ): self {
        return new self(sprintf(
            "Failed to add index '%s' in '%s'. In '%s' migration. DB error: %s",
            $index,
            $table,
            $migrationClass,
            $dbError
        ));
    }

    public static function failedToAddPrimaryKey(
        string $key,
        string $table,
        string $migrationClass,
        string $dbError
    ): self {
        return new self(sprintf(
            "Failed to add primary key '%s' in '%s'. In '%s' migration. DB error: %s",
            $key,
            $table,
            $migrationClass,
            $dbError
        ));
    }

    public static function failedToCreateTable(
        string $table,
        string $migrationClass,
        string $dbError
    ): self {
        return new self(sprintf(
            "Failed to create table '%s'. In '%s' migration. DB error: %s",
            $table,
            $migrationClass,
            $dbError
        ));
    }

    public static function failedToDropColumn(
        string $column,
        string $table,
        string $migrationClass,
        string $dbError
    ): self {
        return new self(sprintf(
            "Failed to drop column '%s' in table '%s'. In '%s' migration. DB error: %s",
            $column,
            $table,
            $migrationClass,
            $dbError
        ));
    }

    public static function failedToDropForeignKey(
        string $key,
        string $table,
        string $migrationClass,
        string $dbError
    ): self {
        return new self(sprintf(
            "Failed to drop foreign key '%s' in '%s'. In '%s' migration. DB error: %s",
            $key,
            $table,
            $migrationClass,
            $dbError
        ));
    }

    public static function failedToDropIndex(
        string $index,
        string $table,
        string $migrationClass,
        string $dbError
    ): self {
        return new self(sprintf(
            "Failed to drop index '%s' in '%s'. In '%s' migration. DB error: %s",
            $index,
            $table,
            $migrationClass,
            $dbError
        ));
    }

    public static function failedToDropPrimaryKey(
        string $table,
        string $migrationClass,
        string $dbError
    ): self {
        return new self(sprintf(
            "Failed to drop primary key in '%s'. In '%s' migration. DB error: %s",
            $table,
            $migrationClass,
            $dbError
        ));
    }

    public static function failedToModifyColumn(
        string $column,
        string $table,
        string $migrationClass,
        string $dbError
    ): self {
        return new self(sprintf(
            "Failed to modify column '%s' in table '%s'. In '%s' migration. DB error: %s",
            $column,
            $table,
            $migrationClass,
            $dbError
        ));
    }

    public static function migrationClassNotFound(string $className, string $fileName): self
    {
        return new self('Migration class cannot be found ' . $className . ' at ' . $fileName);
    }

    public static function migrationEntityEmpty(): self
    {
        return new self('Migration entity is empty. Call Generate::createEntity()');
    }

    public static function migrationsDirNotFound(): self
    {
        return new self('Migrations directory was not found.');
    }

    public static function migrationsDirectoryNotDefined(): self
    {
        return new self('Migrations directory is not defined. Cannot proceed');
    }

    public static function tableMustHaveAtLeastOneColumn(): self
    {
        return new self('Table must have at least one column');
    }

    public static function unspecifiedDatabaseAdapter(): self
    {
        return new self('Unspecified database Adapter in your configuration!');
    }

    public static function versionAlreadyExists(string $version): self
    {
        return new self('Version ' . $version . ' already exists');
    }
}
