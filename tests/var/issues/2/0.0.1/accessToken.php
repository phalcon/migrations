<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Migrations\Mvc\Model\Migration;

class AccessTokenMigration_1 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     * @throws \Phalcon\Db\Exception
     */
    public function morph()
    {
        $this->getConnection()->execute('SET FOREIGN_KEY_CHECKS=0;');

        $this->morphTable('accessToken', [
                'columns' => [
                    new Column(
                        'token',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'notNull' => true,
                            'size' => 80,
                            'first' => true,
                        ]
                    ),
                    new Column(
                        'clientId',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 80,
                            'after' => 'token',
                        ]
                    ),
                    new Column(
                        'userId',
                        [
                            'type' => Column::TYPE_VARCHAR,
                            'size' => 80,
                            'after' => 'clientId',
                        ]
                    ),

                ],
                'indexes' => [
                    new Index('accessToken_pkey', ['token'], 'PRIMARY'),
                ],
                'references' => [
                    new Reference(
                        'fk_accessToken_client_1',
                        [
                            'referencedTable' => 'client',
                            'referencedSchema' => 'public',
                            'columns' => ['clientId'],
                            'referencedColumns' => ['id'],
                            'onUpdate' => 'NO ACTION',
                            'onDelete' => 'NO ACTION',
                        ]
                    ),
                ],
            ]
        );

        $this->getConnection()->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Run the migrations
     *
     * @return void
     */
    public function up()
    {

    }

    /**
     * Reverse the migrations
     *
     * @return void
     */
    public function down()
    {
        $this->getConnection()->dropTable('accessToken');
    }
}
