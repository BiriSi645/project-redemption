<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['user_id','actor_user_id','note_id','task_id','game_room_id','type','message','target_path','notification_key','read_at','created_at'];

    public function inbox(int $userId, int $perPage = 20): array
    {
        return $this->select('notifications.*, users.username AS actor_username, notes.title AS note_title')
            ->join('users', 'users.id = notifications.actor_user_id', 'left')
            ->join('notes', 'notes.id = notifications.note_id', 'left')
            ->where('notifications.user_id', $userId)
            ->orderBy('notifications.created_at', 'DESC')
            ->paginate($perPage);
    }

    public function unreadCount(int $userId): int
    {
        return $this->where('user_id', $userId)->where('read_at', null)->countAllResults();
    }

    public function recentForUser(int $userId, int $limit = 5): array
    {
        return $this->select('notifications.*, users.username AS actor_username')
            ->join('users', 'users.id = notifications.actor_user_id', 'left')
            ->where('notifications.user_id', $userId)
            ->orderBy('notifications.created_at', 'DESC')
            ->limit($limit)->findAll();
    }

    public static function icon(string $type): string
    {
        return match ($type) {
            'note_mention' => '@',
            'task_due' => '✓',
            'game_invite' => '🎮',
            default => '🔔',
        };
    }
}
