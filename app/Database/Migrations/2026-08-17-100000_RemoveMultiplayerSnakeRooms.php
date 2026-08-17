<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveMultiplayerSnakeRooms extends Migration
{
    public function up()
    {
        $roomIds = array_column($this->db->table('game_rooms')->select('id')->where('game', 'snake')->get()->getResultArray(), 'id');
        if ($roomIds === []) return;
        $this->db->table('notifications')->whereIn('game_room_id', $roomIds)->delete();
        $this->db->table('game_rooms')->whereIn('id', $roomIds)->delete();
    }

    public function down()
    {
        // Silinen geçici oyun odaları geri oluşturulmaz.
    }
}
