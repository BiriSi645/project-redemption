<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAnnouncementsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'type' => ['type' => 'VARCHAR', 'constraint' => 30],
            'title' => ['type' => 'VARCHAR', 'constraint' => 150],
            'content' => ['type' => 'TEXT'],
            'target_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'recipient_count' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['type', 'created_at'], false, false, 'idx_announcements_type_created');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('announcements');
    }

    public function down()
    {
        $this->forge->dropTable('announcements');
    }
}
