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

final class Column
{
    public const TYPE_BIGINTEGER   = 'biginteger';
    public const TYPE_BIT          = 'bit';
    public const TYPE_BLOB         = 'blob';
    public const TYPE_BOOLEAN      = 'boolean';
    public const TYPE_CHAR         = 'char';
    public const TYPE_DATE         = 'date';
    public const TYPE_DATETIME     = 'datetime';
    public const TYPE_DECIMAL      = 'decimal';
    public const TYPE_DOUBLE       = 'double';
    public const TYPE_ENUM         = 'enum';
    public const TYPE_FLOAT        = 'float';
    public const TYPE_INTEGER      = 'integer';
    public const TYPE_JSON         = 'json';
    public const TYPE_JSONB        = 'jsonb';
    public const TYPE_LONGBLOB     = 'longblob';
    public const TYPE_LONGTEXT     = 'longtext';
    public const TYPE_MEDIUMBLOB   = 'mediumblob';
    public const TYPE_MEDIUMINTEGER = 'mediuminteger';
    public const TYPE_MEDIUMTEXT   = 'mediumtext';
    public const TYPE_SMALLINTEGER = 'smallinteger';
    public const TYPE_TEXT         = 'text';
    public const TYPE_TIME         = 'time';
    public const TYPE_TIMESTAMP    = 'timestamp';
    public const TYPE_TINYBLOB     = 'tinyblob';
    public const TYPE_TINYINTEGER  = 'tinyinteger';
    public const TYPE_TINYTEXT     = 'tinytext';
    public const TYPE_VARCHAR      = 'varchar';
    private ?string $after;
    private bool $autoIncrement;
    private string $comment;
    private mixed $default;
    private bool $first;
    private bool $hasDefault;
    private bool $notNull;
    private ?array $options;
    private bool $primary;
    private ?int $scale;
    private int|string|null $size;

    private string $type;
    private bool $unsigned;

    public function __construct(
        private readonly string $name,
        array $definition = []
    ) {
        $this->type          = (string) ($definition['type'] ?? self::TYPE_VARCHAR);
        $this->size          = $definition['size']          ?? null;
        $this->scale         = isset($definition['scale'])  ? (int) $definition['scale'] : null;
        $this->notNull       = (bool) ($definition['notNull']       ?? true);
        $this->unsigned      = (bool) ($definition['unsigned']      ?? false);
        $this->autoIncrement = (bool) ($definition['autoIncrement'] ?? false);
        $this->primary       = (bool) ($definition['primary']       ?? false);
        $this->first         = (bool) ($definition['first']         ?? false);
        $this->after         = $definition['after']         ?? null;
        $this->comment       = $definition['comment']       ?? '';
        $this->options       = $definition['options']       ?? null;

        if (array_key_exists('default', $definition)) {
            $this->default    = $definition['default'];
            $this->hasDefault = true;
        } else {
            $this->default    = null;
            $this->hasDefault = false;
        }
    }

    public function getAfterPosition(): ?string
    {
        return $this->after;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function getScale(): ?int
    {
        return $this->scale;
    }

    public function getSize(): int|string|null
    {
        return $this->size;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }

    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    public function isFirst(): bool
    {
        return $this->first;
    }

    public function isNotNull(): bool
    {
        return $this->notNull;
    }

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }
}
