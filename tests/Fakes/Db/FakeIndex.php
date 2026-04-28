<?php

declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Fakes\Db;

/**
 * Simulates a pre-migration Phalcon\Db\Index for testing morphTable backwards compatibility
 * without requiring the Phalcon framework.
 */
class FakeIndex
{
    public function __construct(
        private string $name,
        private array $columns,
        private string $type = ''
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getType(): string
    {
        return $this->type;
    }
}