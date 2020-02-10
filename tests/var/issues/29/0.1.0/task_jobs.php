<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Migrations\Mvc\Model\Migration;

class TaskJobsMigration_10 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     * @throws \Phalcon\Db\Exception
     */
    public function morph()
    {
        $this->morphTable('task_jobs', [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER,
                    'notNull' => true,
                    'autoIncrement' => true,
                    'size' => 20,
                    'first' => true
                ]),
                new Column('task_id', [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 20,
                    'after' => 'id',
                    'notNull' => false
                ]),
                new Column('run_at', [
                    'type' => Column::TYPE_DATETIME,
                    'notNull' => true,
                    'size' => 1,
                    'after' => 'task_id'
                ]),
                new Column('status', [
                    'type' => Column::TYPE_INTEGER,
                    'default' => "0",
                    'size' => 1,
                    'after' => 'run_at'
                ]),
                new Column('result', [
                    'type' => Column::TYPE_TEXT,
                    'size' => 1,
                    'after' => 'status'
                ])],
            'indexes' => [
                new Index('PRIMARY', ['id'], 'PRIMARY'),
                new Index('task_jobs_id_uindex', ['id'], 'UNIQUE'),
                new Index('task_jobs_tasks_id_fk', ['task_id'], '')
            ],
            'references' => [
                new Reference('task_jobs_tasks_id_fk', [
                    'referencedTable' => 'tasks',
                    'referencedSchema' => '',
                    'columns' => ['task_id'],
                    'referencedColumns' => ['id'],
                    'onUpdate' => 'RESTRICT',
                    'onDelete' => 'SET NULL'
                ])
            ],
            'options' => [
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '103706',
                'ENGINE' => 'InnoDB',
                'TABLE_COLLATION' => 'utf8mb4_general_ci'
            ],
        ]);
    }

    /**
     * Run the migrations
     *
     * @return void
     */
    public function up()
    {
        //
    }

    /**
     * Reverse the migrations
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
