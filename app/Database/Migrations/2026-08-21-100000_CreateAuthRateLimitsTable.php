<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateAuthRateLimitsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'rate_key' => ['type' => 'CHAR', 'constraint' => 64],
            'attempts' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'expires_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('rate_key');
        $this->forge->addKey('expires_at');
        $this->forge->createTable('auth_rate_limits', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('auth_rate_limits', true);
    }
}
