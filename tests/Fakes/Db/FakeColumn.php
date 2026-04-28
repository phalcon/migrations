<?php

declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Fakes\Db;

/**
 * Simulates a pre-migration Phalcon\Db\Column (integer TYPE_* constants)
 * for testing PhalconColumnBridge without requiring the Phalcon framework.
 */
class FakeColumn
{
    public const TYPE_INTEGER      = 0;
    public const TYPE_DATE         = 1;
    public const TYPE_VARCHAR      = 2;
    public const TYPE_DECIMAL      = 3;
    public const TYPE_DATETIME     = 4;
    public const TYPE_CHAR         = 5;
    public const TYPE_TEXT         = 6;
    public const TYPE_FLOAT        = 7;
    public const TYPE_BOOLEAN      = 8;
    public const TYPE_DOUBLE       = 9;
    public const TYPE_TINYBLOB     = 10;
    public const TYPE_BLOB         = 11;
    public const TYPE_MEDIUMBLOB   = 12;
    public const TYPE_LONGBLOB     = 13;
    public const TYPE_BIGINTEGER   = 14;
    public const TYPE_JSON         = 15;
    public const TYPE_JSONB        = 16;
    public const TYPE_TIMESTAMP    = 17;
    public const TYPE_ENUM         = 18;
    public const TYPE_BIT          = 19;
    public const TYPE_TIME         = 20;
    public const TYPE_MEDIUMINTEGER = 21;
    public const TYPE_SMALLINTEGER  = 22;
    public const TYPE_MEDIUMTEXT    = 23;
    public const TYPE_LONGTEXT      = 24;
    public const TYPE_TINYTEXT      = 25;
    public const TYPE_TINYINTEGER   = 26;

    public function __construct(private string $name, private array $definition)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): int
    {
        return (int) ($this->definition['type'] ?? self::TYPE_INTEGER);
    }

    public function getSize(): int|string|null
    {
        return $this->definition['size'] ?? null;
    }

    public function getScale(): ?int
    {
        return isset($this->definition['scale']) ? (int) $this->definition['scale'] : null;
    }

    public function isNotNull(): bool
    {
        return (bool) ($this->definition['notNull'] ?? false);
    }

    public function isUnsigned(): bool
    {
        return (bool) ($this->definition['unsigned'] ?? false);
    }

    public function isAutoIncrement(): bool
    {
        return (bool) ($this->definition['autoIncrement'] ?? false);
    }

    public function isPrimary(): bool
    {
        return (bool) ($this->definition['primary'] ?? false);
    }

    public function isFirst(): bool
    {
        return (bool) ($this->definition['first'] ?? false);
    }

    public function getAfterPosition(): ?string
    {
        return $this->definition['after'] ?? null;
    }

    public function getComment(): string
    {
        return (string) ($this->definition['comment'] ?? '');
    }

    public function hasDefault(): bool
    {
        return array_key_exists('default', $this->definition);
    }

    public function getDefault(): mixed
    {
        return $this->definition['default'] ?? null;
    }
}
