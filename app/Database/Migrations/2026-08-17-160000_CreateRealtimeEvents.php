<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRealtimeEvents extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'recipient_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'payload' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['recipient_user_id', 'id'], false, false, 'idx_realtime_events_recipient');
        $this->forge->addKey('created_at', false, false, 'idx_realtime_events_created_at');
        $this->forge->createTable('realtime_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('realtime_events', true);
    }
}
