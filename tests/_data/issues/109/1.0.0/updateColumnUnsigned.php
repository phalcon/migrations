<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Migrations\Mvc\Model\Migration;

class UpdateColumnUnsignedMigration_100 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     * @throws \Phalcon\Db\Exception
     */
    public function morph()
    {
        $this->morphTable('update_unsigned_column', [
            'columns' => [
                new Column(
                    'id',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'size' => 10,
                        'unsigned' => true,
                        'notNull' => true,
                        'first' => true,
                        'primary' => true,
                        'autoIncrement' => true,
                    ]
                )
            ],
        ]);
    }
}
