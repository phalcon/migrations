<?php

/**
 * Old-format migration: uses Phalcon\Db\* namespace (pre-library-decoupling).
 * morphTable() converts these via PhalconColumnBridge transparently.
 */

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Migrations\Mvc\Model\Migration;

class BcOldUsersMigration_100 extends Migration
{
    public function morph(): void
    {
        $this->morphTable('bc_old_users', [
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
