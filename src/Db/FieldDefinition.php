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

use Phalcon\Db\ColumnInterface;

class FieldDefinition
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ColumnInterface
     */
    private $currentColumn;

    /**
     * @var FieldDefinition|null
     */
    private $previousField;

    /**
     * @var FieldDefinition|null
     */
    private $nextField;

    public function __construct(
        ColumnInterface $column,
        ?FieldDefinition $previousField = null,
        ?FieldDefinition $nextField = null
    ) {
        $this->name = $column->getName();
        $this->currentColumn = $column;
        $this->previousField = $previousField;
        $this->nextField = $nextField;
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

    public function getColumn(): ColumnInterface
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
     * @param FieldDefinition[] $externalFieldset Set of another field definitions to compare field between
     *
     * @return self|null
     */
    public function getPairedDefinition(array $externalFieldset): ?FieldDefinition
    {
        if (isset($externalFieldset[$this->getName()])) {
            return $externalFieldset[$this->getName()];
        }

        $possiblePairedField = null;
        if (null !== $this->previousField) {
            $prevField = $externalFieldset[$this->previousField->getName()] ?? null;
            if (null !== $prevField) {
                $possiblePairedField = $prevField->getNext();
            }
        }
        if (null === $possiblePairedField && null !== $this->nextField) {
            $nextField = $externalFieldset[$this->nextField->getName()] ?? null;
            if (null !== $nextField) {
                $possiblePairedField = $nextField->getPrevious();
            }
        }

        if (null === $possiblePairedField) {
            return null;
        }

        if ($this->isChangedData($possiblePairedField)) {
            return null;
        }

        return $possiblePairedField;
    }

    public function isChanged(FieldDefinition $comparingFieldDefinition): bool
    {
        return $this->isChangedName($comparingFieldDefinition) || $this->isChangedData($comparingFieldDefinition);
    }

    public function isChangedName(FieldDefinition $comparingFieldDefinition): bool
    {
        return $this->currentColumn->getName() !== $comparingFieldDefinition->getColumn()->getName();
    }

    public function isChangedData(FieldDefinition $comparingFieldDefinition): bool
    {
        $paramsToCheck = get_class_methods(ColumnInterface::class);
        unset($paramsToCheck['getName']);

        $comparingFieldColumn = $comparingFieldDefinition->getColumn();
        foreach ($paramsToCheck as $method) {
            if ($this->currentColumn->$method() !== $comparingFieldColumn->$method()) {
                return true;
            }
        }

        return false;
    }
}
