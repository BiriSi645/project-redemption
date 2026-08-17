<?php

namespace App\Models;

use App\Libraries\RealtimePublisher;
use CodeIgniter\Model;

class DirectMessageModel extends Model
{
    protected $table = 'direct_messages';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['conversation_id','sender_id','body','read_at','deleted_by_sender','deleted_by_recipient','created_at'];
    protected $validationRules = ['body' => ['label'=>'Mesaj','rules'=>'required|max_length[2000]']];
    protected $validationMessages = ['body'=>['required'=>'Boş mesaj gönderilemez.','max_length'=>'Mesaj en fazla 2000 karakter olabilir.']];
    protected $afterInsert = ['publishRealtimeAfterInsert'];

    protected function publishRealtimeAfterInsert(array $eventData): array
    {
        if (empty($eventData['result']) || empty($eventData['data']['conversation_id'])) {
            return $eventData;
        }

        $conversationId = (int) $eventData['data']['conversation_id'];
        $conversation = $this->db->table('direct_conversations')
            ->select('user_one_id, user_two_id')
            ->where('id', $conversationId)
            ->get()
            ->getRowArray();

        if (! $conversation) {
            return $eventData;
        }

        (new RealtimePublisher())->user(
            [(int) $conversation['user_one_id'], (int) $conversation['user_two_id']],
            'direct-message',
            [
                'messageId' => (int) ($eventData['id'] ?? 0),
                'conversationId' => $conversationId,
                'senderId' => (int) ($eventData['data']['sender_id'] ?? 0),
            ]
        );

        return $eventData;
    }
}
