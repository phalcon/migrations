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

class FieldDefinition
{
    private string $name;

    private ?FieldDefinition $previousField;

    private ?FieldDefinition $nextField;

    public function __construct(
        private Column $currentColumn,
        ?FieldDefinition $previousField = null,
        ?FieldDefinition $nextField = null
    ) {
        $this->name          = $currentColumn->getName();
        $this->previousField = $previousField;
        $this->nextField     = $nextField;
    }

    public function setPrevious(?FieldDefinition $field = null): void
    {
        $this->previousField = $field;
    }

    public function setNext(?FieldDefinition $field = null): void
    {
        $this->nextField = $field;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColumn(): Column
    {
        return $this->currentColumn;
    }

    public function getPrevious(): ?FieldDefinition
    {
        return $this->previousField ?? null;
    }

    public function getNext(): ?FieldDefinition
    {
        return $this->nextField ?? null;
    }

    /**
     * @param FieldDefinition[] $externalFieldset
     */
    public function getPairedDefinition(array $externalFieldset): ?FieldDefinition
    {
        if (isset($externalFieldset[$this->getName()])) {
            return $externalFieldset[$this->getName()];
        }

        $possiblePairedField = null;
        if ($this->previousField !== null) {
            $prevField = $externalFieldset[$this->previousField->getName()] ?? null;
            if ($prevField !== null) {
                $possiblePairedField = $prevField->getNext();
            }
        }

        if ($possiblePairedField === null && $this->nextField !== null) {
            $nextField = $externalFieldset[$this->nextField->getName()] ?? null;
            if ($nextField !== null) {
                $possiblePairedField = $nextField->getPrevious();
            }
        }

        if ($possiblePairedField === null) {
            return null;
        }

        if ($this->isChangedData($possiblePairedField)) {
            return null;
        }

        return $possiblePairedField;
    }

    public function isChanged(FieldDefinition $other): bool
    {
        return $this->isChangedName($other) || $this->isChangedData($other);
    }

    public function isChangedName(FieldDefinition $other): bool
    {
        return $this->currentColumn->getName() !== $other->getColumn()->getName();
    }

    public function isChangedData(FieldDefinition $other): bool
    {
        $a = $this->currentColumn;
        $b = $other->getColumn();

        return $a->getType()          !== $b->getType()
            || $a->getSize()          !== $b->getSize()
            || $a->getScale()         !== $b->getScale()
            || $a->isNotNull()        !== $b->isNotNull()
            || $a->isUnsigned()       !== $b->isUnsigned()
            || $a->isAutoIncrement()  !== $b->isAutoIncrement()
            || $a->isPrimary()        !== $b->isPrimary()
            || $a->hasDefault()       !== $b->hasDefault()
            || $a->getDefault()       !== $b->getDefault();
    }
}
