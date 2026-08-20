<?php

namespace App\Models;

use CodeIgniter\Model;

class GameRoomPlayerModel extends Model
{
    protected $table = 'game_room_players';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'room_id', 'user_id', 'seat_index', 'player_type', 'display_name',
        'bot_difficulty', 'team_no', 'is_ready', 'last_seen_at',
    ];

    public function forRoom(int $roomId): array
    {
        return $this->where('room_id', $roomId)->orderBy('seat_index')->findAll();
    }
}
