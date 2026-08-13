<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGameRoomPresence extends Migration
{
    public function up()
    {
        $this->forge->addColumn('game_rooms', [
            'host_room_seen_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'guest_user_id'],
            'guest_room_seen_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'host_room_seen_at'],
        ]);
        $this->db->query('CREATE INDEX idx_game_rooms_presence ON game_rooms (status, host_room_seen_at, guest_room_seen_at)');
        $this->db->table('game_rooms')->whereIn('status', ['waiting', 'playing'])->update([
            'host_room_seen_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function down()
    {
        $this->db->query('DROP INDEX idx_game_rooms_presence ON game_rooms');
        $this->forge->dropColumn('game_rooms', ['host_room_seen_at', 'guest_room_seen_at']);
    }
}
