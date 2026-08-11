<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDueTimeToTasks extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tasks', [
            'due_time' => [
                'type'  => 'TIME',
                'null'  => true,
                'after' => 'due_date',
            ],
        ]);

        $this->db->table('tasks')
            ->where('due_date IS NOT NULL', null, false)
            ->where('due_time', null)
            ->update(['due_time' => '23:59:59']);
    }

    public function down()
    {
        $this->forge->dropColumn('tasks', 'due_time');
    }
}
