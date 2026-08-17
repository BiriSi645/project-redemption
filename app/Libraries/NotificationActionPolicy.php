<?php

namespace App\Libraries;

class NotificationActionPolicy
{
    public static function safeGetTarget(array $notification): string
    {
        if (($notification['type'] ?? '') === 'game_invite') {
            return 'notifications';
        }

        $target = (string) ($notification['target_path'] ?? '');
        return $target !== '' && preg_match('#^[a-zA-Z0-9/_-]+$#', $target)
            ? $target
            : 'notifications';
    }
}
