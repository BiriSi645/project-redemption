<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserBiography extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('bio', 'users')) {
            $this->forge->addColumn('users', [
                'bio' => [
                    'type' => 'VARCHAR',
                    'constraint' => 300,
                    'null' => true,
                    'after' => 'username',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('bio', 'users')) {
            $this->forge->dropColumn('users', 'bio');
        }
    }
}
