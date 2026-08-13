<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExpandNotificationCenter extends Migration
{
    public function up()
    {
        $this->forge->addColumn('notifications', [
            'task_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'note_id'],
            'game_room_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'task_id'],
            'target_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'message'],
            'notification_key' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true, 'after' => 'target_path'],
        ]);
        $this->db->query('CREATE UNIQUE INDEX uq_notifications_key ON notifications (notification_key)');
        $this->db->query('CREATE INDEX idx_notifications_task ON notifications (task_id)');
        $this->db->query('CREATE INDEX idx_notifications_room ON notifications (game_room_id)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX uq_notifications_key ON notifications');
        $this->db->query('DROP INDEX idx_notifications_task ON notifications');
        $this->db->query('DROP INDEX idx_notifications_room ON notifications');
        $this->forge->dropColumn('notifications', ['task_id', 'game_room_id', 'target_path', 'notification_key']);
    }
}
