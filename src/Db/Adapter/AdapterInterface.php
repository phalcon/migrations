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

interface AdapterInterface
{
    public function addColumn(string $table, string $schema, Column $column): void;

    public function addForeignKey(string $table, string $schema, Reference $reference): void;

    public function addIndex(string $table, string $schema, Index $index): void;

    public function addPrimaryKey(string $table, string $schema, Index $index): void;

    public function begin(): void;

    public function commit(): void;

    public function createTable(string $table, string $schema, array $definition): void;

    public function dropColumn(string $table, string $schema, string $column): void;

    public function dropForeignKey(string $table, string $schema, string $name): void;

    public function dropIndex(string $table, string $schema, string $name): void;

    public function dropPrimaryKey(string $table, string $schema): void;

    public function dropTable(string $table, string $schema = ''): void;

    public function execute(string $sql, array $values = []): void;

    public function fetchAll(string $sql, array $values = []): array;

    public function fetchOne(string $sql, array $values = []): array;
    public function getConnection(): Connection;

    public function getCurrentSchema(): string;

    public function getTableOptions(string $schema, string $table): array;

    /** @return Column[] */
    public function listColumns(string $schema, string $table): array;

    /** @return Index[] */
    public function listIndexes(string $schema, string $table): array;

    /** @return Reference[] */
    public function listReferences(string $schema, string $table): array;

    /** @return string[] */
    public function listTables(string $schema): array;

    public function modifyColumn(string $table, string $schema, Column $new, Column $current): void;

    public function quote(string $value): string;

    public function rollback(): void;

    public function tableExists(string $table, string $schema = ''): bool;
}
