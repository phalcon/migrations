<?php

/**
 * New-format migration: uses Phalcon\Migrations\Db\* namespace (current format).
 */

use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Db\Index;
use Phalcon\Migrations\Mvc\Model\Migration;

class BcNewUsersMigration_100 extends Migration
{
    public function morph(): void
    {
        $this->morphTable('bc_new_users', [
            'columns' => [
                new Column(
                    'id',
                    [
                        'type'          => Column::TYPE_INTEGER,
                        'size'          => 11,
                        'notNull'       => true,
                        'autoIncrement' => true,
                        'first'         => true,
                    ]
                ),
                new Column(
                    'username',
                    [
                        'type'    => Column::TYPE_VARCHAR,
                        'size'    => 100,
                        'notNull' => true,
                    ]
                ),
                new Column(
                    'email',
                    [
                        'type'    => Column::TYPE_VARCHAR,
                        'size'    => 255,
                        'notNull' => false,
                    ]
                ),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], 'PRIMARY'),
            ],
        ]);
    }

    public function up(): void
    {
    }

    public function down(): void
    {
    }
}
