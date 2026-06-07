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

namespace Phalcon\Migrations\Db;

final class Reference
{
    private array $columns;
    private string $onDelete;
    private string $onUpdate;
    private array $referencedColumns;
    private ?string $referencedSchema;
    private string $referencedTable;

    public function __construct(
        private readonly string $name,
        array $definition = []
    ) {
        $this->referencedSchema  = $definition['referencedSchema']  ?? null;
        $this->referencedTable   = $definition['referencedTable']   ?? '';
        $this->columns           = $definition['columns']           ?? [];
        $this->referencedColumns = $definition['referencedColumns'] ?? [];
        $this->onDelete          = $definition['onDelete']          ?? '';
        $this->onUpdate          = $definition['onUpdate']          ?? '';
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOnDelete(): string
    {
        return $this->onDelete;
    }

    public function getOnUpdate(): string
    {
        return $this->onUpdate;
    }

    public function getReferencedColumns(): array
    {
        return $this->referencedColumns;
    }

    public function getReferencedSchema(): ?string
    {
        return $this->referencedSchema;
    }

    public function getReferencedTable(): string
    {
        return $this->referencedTable;
    }
}
