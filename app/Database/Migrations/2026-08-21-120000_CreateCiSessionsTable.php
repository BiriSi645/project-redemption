<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateCiSessionsTable extends Migration
{
    protected $DBGroup = 'default';

    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => false],
            '`timestamp` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL',
            'data' => ['type' => 'BLOB', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('timestamp');
        $this->forge->createTable('ci_sessions', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('ci_sessions', true);
    }
}
