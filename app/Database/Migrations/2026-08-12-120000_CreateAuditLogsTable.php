<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'method' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],
            'path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'status_code' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'created_at']);
        $this->forge->addKey(['action', 'created_at']);
        $this->forge->addKey('status_code');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_audit_logs_user');
        $this->forge->createTable('audit_logs');
    }

    public function down()
    {
        $this->forge->dropTable('audit_logs');
    }
}
