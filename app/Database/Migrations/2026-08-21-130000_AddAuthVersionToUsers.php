<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAuthVersionToUsers extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('auth_version', 'users')) {
            $this->forge->addColumn('users', [
                'auth_version' => [
                    'type' => 'INT',
                    'constraint' => 10,
                    'unsigned' => true,
                    'default' => 1,
                    'after' => 'password_hash',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('auth_version', 'users')) {
            $this->forge->dropColumn('users', 'auth_version');
        }
    }
}
