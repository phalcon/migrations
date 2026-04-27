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

/**
 * Converts a Phalcon\Db\ColumnInterface (integer type constants) to our
 * Column (string type constants) so that migration files generated before
 * this library removed its Phalcon\Db dependency continue to work unchanged.
 */
final class PhalconColumnBridge
{
    /**
     * Maps Phalcon\Db\Column::TYPE_* integer values to Column::TYPE_* strings.
     *
     * @var array<int,string>
     */
    private const TYPE_MAP = [
        0  => Column::TYPE_INTEGER,
        1  => Column::TYPE_DATE,
        2  => Column::TYPE_VARCHAR,
        3  => Column::TYPE_DECIMAL,
        4  => Column::TYPE_DATETIME,
        5  => Column::TYPE_CHAR,
        6  => Column::TYPE_TEXT,
        7  => Column::TYPE_FLOAT,
        8  => Column::TYPE_BOOLEAN,
        9  => Column::TYPE_DOUBLE,
        10 => Column::TYPE_TINYBLOB,
        11 => Column::TYPE_BLOB,
        12 => Column::TYPE_MEDIUMBLOB,
        13 => Column::TYPE_LONGBLOB,
        14 => Column::TYPE_BIGINTEGER,
        15 => Column::TYPE_JSON,
        16 => Column::TYPE_JSONB,
        17 => Column::TYPE_TIMESTAMP,
        18 => Column::TYPE_ENUM,
        19 => Column::TYPE_BIT,
        20 => Column::TYPE_TIME,
        21 => Column::TYPE_MEDIUMINTEGER,
        22 => Column::TYPE_SMALLINTEGER,
        23 => Column::TYPE_MEDIUMTEXT,
        24 => Column::TYPE_LONGTEXT,
        25 => Column::TYPE_TINYTEXT,
        26 => Column::TYPE_TINYINTEGER,
    ];

    public static function fromPhalcon(object $column): Column
    {
        $rawType = $column->getType();
        $type    = is_int($rawType)
            ? (self::TYPE_MAP[$rawType] ?? Column::TYPE_VARCHAR)
            : (string) $rawType;

        $definition = [
            'type'          => $type,
            'notNull'       => $column->isNotNull(),
            'unsigned'      => $column->isUnsigned(),
            'autoIncrement' => $column->isAutoIncrement(),
            'primary'       => $column->isPrimary(),
            'first'         => $column->isFirst(),
            'after'         => $column->getAfterPosition(),
            'comment'       => method_exists($column, 'getComment') ? $column->getComment() : '',
        ];

        $size = $column->getSize();
        if ($size !== null && $size !== 0 && $size !== '') {
            $definition['size'] = $size;
        }

        $scale = $column->getScale();
        if ($scale !== null && $scale !== 0) {
            $definition['scale'] = $scale;
        }

        if ($column->hasDefault()) {
            $definition['default'] = $column->getDefault();
        }

        return new Column($column->getName(), $definition);
    }
}
