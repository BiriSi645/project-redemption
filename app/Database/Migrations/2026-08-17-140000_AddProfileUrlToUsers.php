<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProfileUrlToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'profile_url' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'after' => 'bio',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'profile_url');
    }
}
