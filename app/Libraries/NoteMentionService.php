<?php

namespace App\Libraries;

use App\Models\NoteMentionModel;
use App\Models\NotificationModel;
use App\Models\UserModel;

class NoteMentionService
{
    private const PATTERN = '/(?<![\p{L}\p{N}_.-])@([\p{L}\p{N}_](?:[\p{L}\p{N}_.-]{1,98}[\p{L}\p{N}_]))/u';

    public function usernames(string $text): array
    {
        preg_match_all(self::PATTERN, $text, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    public function mentionedUsers(string $text, int $authorId): array
    {
        $usernames = $this->usernames($text);
        if ($usernames === []) {
            return [];
        }

        return (new UserModel())->select('id, username')
            ->whereIn('username', $usernames)
            ->where('id !=', $authorId)
            ->where('is_active', 1)
            ->findAll();
    }

    public function sync(int $noteId, int $authorId, string $authorName, array $users): void
    {
        $mentionModel = new NoteMentionModel();
        $existing = array_map('intval', array_column($mentionModel->where('note_id', $noteId)->findAll(), 'user_id'));
        $newIds = array_map('intval', array_column($users, 'id'));
        $added = array_diff($newIds, $existing);
        $removed = array_diff($existing, $newIds);
        $now = date('Y-m-d H:i:s');
        $db = db_connect();
        $db->transStart();

        if ($removed !== []) {
            $mentionModel->where('note_id', $noteId)->whereIn('user_id', $removed)->delete();
            (new NotificationModel())->where('note_id', $noteId)
                ->where('type', 'note_mention')->whereIn('user_id', $removed)->delete();
        }

        foreach ($added as $userId) {
            $mentionModel->insert(['note_id' => $noteId, 'user_id' => $userId, 'created_at' => $now]);
            (new NotificationModel())->insert([
                'user_id' => $userId,
                'actor_user_id' => $authorId,
                'note_id' => $noteId,
                'type' => 'note_mention',
                'message' => $authorName . ' sizi bir notta etiketledi.',
                'created_at' => $now,
            ]);
        }

        $db->transComplete();
    }
}
