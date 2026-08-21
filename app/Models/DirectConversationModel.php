<?php

namespace App\Models;

use CodeIgniter\Model;

class DirectConversationModel extends Model
{
    protected $table = 'direct_conversations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['user_one_id','user_two_id','last_message_at'];

    public function forUser(int $userId, int $perPage = 20): array
    {
        $escaped = $this->db->escape($userId);
        return $this->select('direct_conversations.*')
            ->select("CASE WHEN user_one_id = {$escaped} THEN user_two_id ELSE user_one_id END AS other_user_id", false)
            ->select("CASE WHEN user_one_id = {$escaped} THEN COALESCE(user_two.username, 'Silinmiş kullanıcı') ELSE COALESCE(user_one.username, 'Silinmiş kullanıcı') END AS other_username", false)
            ->select("(SELECT body FROM direct_messages dm WHERE dm.conversation_id = direct_conversations.id AND ((dm.sender_id = {$escaped} AND dm.deleted_by_sender = 0) OR ((dm.sender_id != {$escaped} OR dm.sender_id IS NULL) AND dm.deleted_by_recipient = 0)) ORDER BY dm.id DESC LIMIT 1) AS last_body", false)
            ->select("(SELECT COUNT(*) FROM direct_messages dm WHERE dm.conversation_id = direct_conversations.id AND (dm.sender_id != {$escaped} OR dm.sender_id IS NULL) AND dm.read_at IS NULL AND dm.deleted_by_recipient = 0) AS unread_count", false)
            ->join('users user_one', 'user_one.id = direct_conversations.user_one_id', 'left')
            ->join('users user_two', 'user_two.id = direct_conversations.user_two_id', 'left')
            ->groupStart()->where('user_one_id', $userId)->orWhere('user_two_id', $userId)->groupEnd()
            ->orderBy('COALESCE(last_message_at, direct_conversations.created_at)', 'DESC', false)
            ->paginate($perPage);
    }

    public function unreadCount(int $userId): int
    {
        return (int) $this->db->table('direct_messages dm')
            ->join('direct_conversations dc', 'dc.id = dm.conversation_id')
            ->groupStart()->where('dc.user_one_id', $userId)->orWhere('dc.user_two_id', $userId)->groupEnd()
            ->groupStart()->where('dm.sender_id !=', $userId)->orWhere('dm.sender_id', null)->groupEnd()
            ->where('dm.read_at', null)
            ->where('dm.deleted_by_recipient', 0)->countAllResults();
    }

    public function recentForUser(int $userId, int $limit = 5): array
    {
        $escaped = $this->db->escape($userId);
        return $this->select('direct_conversations.id, direct_conversations.last_message_at')
            ->select("CASE WHEN user_one_id = {$escaped} THEN user_two_id ELSE user_one_id END AS other_user_id", false)
            ->select("CASE WHEN user_one_id = {$escaped} THEN COALESCE(user_two.username, 'Silinmiş kullanıcı') ELSE COALESCE(user_one.username, 'Silinmiş kullanıcı') END AS other_username", false)
            ->select("(SELECT body FROM direct_messages dm WHERE dm.conversation_id = direct_conversations.id AND ((dm.sender_id = {$escaped} AND dm.deleted_by_sender = 0) OR ((dm.sender_id != {$escaped} OR dm.sender_id IS NULL) AND dm.deleted_by_recipient = 0)) ORDER BY dm.id DESC LIMIT 1) AS last_body", false)
            ->select("(SELECT COUNT(*) FROM direct_messages dm WHERE dm.conversation_id = direct_conversations.id AND (dm.sender_id != {$escaped} OR dm.sender_id IS NULL) AND dm.read_at IS NULL AND dm.deleted_by_recipient = 0) AS unread_count", false)
            ->join('users user_one', 'user_one.id = direct_conversations.user_one_id', 'left')
            ->join('users user_two', 'user_two.id = direct_conversations.user_two_id', 'left')
            ->groupStart()->where('user_one_id', $userId)->orWhere('user_two_id', $userId)->groupEnd()
            ->orderBy('COALESCE(last_message_at, direct_conversations.created_at)', 'DESC', false)
            ->limit($limit)->findAll();
    }
}
