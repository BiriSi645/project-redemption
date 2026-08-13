<?php

namespace App\Models;

use CodeIgniter\Model;

class GameRoomModel extends Model
{
    protected $table = 'game_rooms';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['code', 'game', 'difficulty', 'host_user_id', 'guest_user_id', 'host_room_seen_at', 'guest_room_seen_at', 'status', 'state', 'version'];

    public function withPlayers(string $code): ?array
    {
        return $this->select('game_rooms.*, host.username AS host_username, guest.username AS guest_username')
            ->join('users host', 'host.id = game_rooms.host_user_id')
            ->join('users guest', 'guest.id = game_rooms.guest_user_id', 'left')
            ->where('game_rooms.code', strtoupper($code))->first();
    }
}
