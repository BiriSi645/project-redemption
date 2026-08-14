<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPasswordResetToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'password_reset_token' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'password_hash',
            ],

            'password_reset_expires_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'password_reset_token',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', [
            'password_reset_token',
            'password_reset_expires_at',
        ]);
    }
}
