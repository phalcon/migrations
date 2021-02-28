<?php

use Phalcon\Db\Column;
use Phalcon\Migrations\Mvc\Model\Migration;

class ForeignKeysTable2Migration_101 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     * @throws \Phalcon\Db\Exception
     */
    public function morph()
    {
        $this->morphTable('foreign_keys_table2', [
            'columns' => [
                new Column(
                    'id',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'notNull' => true,
                        'autoIncrement' => true,
                        'size' => 11,
                        'first' => true,
                    ]
                ),
                new Column(
                    'name',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'notNull' => true,
                        'autoIncrement' => true,
                        'size' => 11,
                    ]
                ),
                new Column(
                    'foreign_id',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'notNull' => true,
                        'size' => 11,
                    ]
                ),
            ],
        ]);
    }
}
