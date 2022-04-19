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

namespace Phalcon\Migrations\Tests\Unit\Db;

use Codeception\Test\Unit;
use Phalcon\Db\Column;
use Phalcon\Migrations\Db\FieldDefinition;

final class FieldDefinitionTest extends Unit
{
    public const COLUMN_NAME = 'login';
    public const COLUMN_DEF = [
        'type' => Column::TYPE_VARCHAR,
        'notNull' => true,
        'size' => 2047,
        'after' => 'id',
    ];

    public const NEW_COLUMN_NAME = 'username';
    public const NEW_COLUMN_DEF = [
        'type' => Column::TYPE_VARCHAR,
        'notNull' => true,
        'size' => 4096,
        'after' => 'id',
    ];

    public const PREV_COLUMN = 'id';
    public const ID_COLUMN_DEF = [
        'type' => Column::TYPE_INTEGER,
        'notNull' => true,
        'autoIncrement' => true,
        'size' => 11,
        'first' => true,
    ];

    public const NEXT_COLUMN = 'password';
    public const PASSWORD_COLUMN_DEF = [
        'type' => Column::TYPE_VARCHAR,
        'notNull' => true,
        'size' => 2047,
    ];

    public function testCreate(): void
    {
        $column = new Column(self::COLUMN_NAME, self::COLUMN_DEF);
        $fieldDefinition = new FieldDefinition($column);

        $this->assertSame($column->getName(), $fieldDefinition->getName());
    }

    public function testSetPrevAndNext(): void
    {
        $column = new Column(self::COLUMN_NAME, self::COLUMN_DEF);
        $fieldDefinition = new FieldDefinition($column);

        $prevColumn = new Column(self::PREV_COLUMN, self::ID_COLUMN_DEF);
        $prevFieldDefinition = new FieldDefinition($prevColumn);
        $fieldDefinition->setPrevious($prevFieldDefinition);

        $nextColumn = new Column(self::NEXT_COLUMN, self::PASSWORD_COLUMN_DEF);
        $nextFieldDefinition = new FieldDefinition($nextColumn);
        $fieldDefinition->setNext($nextFieldDefinition);

        $this->assertSame($prevColumn->getName(), $fieldDefinition->getPrevious()->getColumn()->getName());
        $this->assertSame($nextColumn->getName(), $fieldDefinition->getNext()->getColumn()->getName());
    }

    public function testNameChanged(): void
    {
        $column = new Column(self::COLUMN_NAME, self::COLUMN_DEF);
        $columnChanged = new Column(self::NEW_COLUMN_NAME, self::COLUMN_DEF);

        $fieldDefinition = new FieldDefinition($column);
        $fieldDefinitionChanged = new FieldDefinition($columnChanged);

        $prevFieldDefinition = $this->createPrev($fieldDefinition);
        $nextFieldDefinition = $this->createNext($fieldDefinition);

        $localFields = [];
        $localFields[$fieldDefinition->getName()] = $fieldDefinition;
        $localFields[$prevFieldDefinition->getName()] = $prevFieldDefinition;
        $localFields[$nextFieldDefinition->getName()] = $nextFieldDefinition;

        $pairedDefinition = $fieldDefinitionChanged->getPairedDefinition($localFields);

        $this->assertNull($pairedDefinition);
    }

    public function testIsChangedData(): void
    {
        $column = new Column(self::COLUMN_NAME, self::COLUMN_DEF);
        $fieldDefinition = new FieldDefinition($column);

        $columnChanged = new Column(self::NEW_COLUMN_NAME, self::NEW_COLUMN_DEF);
        $fieldDefinitionChanged = new FieldDefinition($columnChanged);

        $this->assertFalse($fieldDefinition->isChangedData($fieldDefinition));
        $this->assertTrue($fieldDefinition->isChangedData($fieldDefinitionChanged));
    }

    private function createPrev(FieldDefinition $fieldDefinition): FieldDefinition
    {
        $prevColumn = new Column(self::PREV_COLUMN, self::ID_COLUMN_DEF);
        $prevFieldDefinition = new FieldDefinition($prevColumn);
        $fieldDefinition->setPrevious($prevFieldDefinition);
        $prevFieldDefinition->setNext($fieldDefinition);

        return $prevFieldDefinition;
    }

    private function createNext(FieldDefinition $fieldDefinition): FieldDefinition
    {
        $nextColumn = new Column(self::NEXT_COLUMN, self::PASSWORD_COLUMN_DEF);
        $nextFieldDefinition = new FieldDefinition($nextColumn);
        $fieldDefinition->setNext($nextFieldDefinition);
        $nextFieldDefinition->setPrevious($fieldDefinition);

        return $nextFieldDefinition;
    }
}
