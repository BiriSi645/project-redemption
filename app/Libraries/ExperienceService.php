<?php

namespace App\Libraries;

use App\Models\ExperienceEventModel;
use App\Models\UserModel;
use Throwable;

class ExperienceService
{
    private const RULES = [
        'task_completed' => ['points' => 20, 'daily' => 200, 'cooldown' => 0],
        'project_item_completed' => ['points' => 20, 'daily' => 200, 'cooldown' => 0],
        'habit_completed' => ['points' => 10, 'daily' => 100, 'cooldown' => 0],
        'public_note' => ['points' => 15, 'daily' => 45, 'cooldown' => 1800],
    ];

    public function award(int $userId, string $eventType, string $sourceKey): array
    {
        $rule = self::RULES[$eventType] ?? null;
        if ($userId < 1 || ! $rule) return ['awarded' => false, 'points' => 0, 'reason' => 'invalid'];

        $events = new ExperienceEventModel();
        if ($events->where(['user_id' => $userId, 'event_type' => $eventType, 'source_key' => $sourceKey])->first()) {
            return ['awarded' => false, 'points' => 0, 'reason' => 'duplicate'];
        }

        $today = date('Y-m-d 00:00:00');
        $earnedToday = (int) ($events->selectSum('points', 'total')->where('user_id', $userId)->where('event_type', $eventType)->where('created_at >=', $today)->first()['total'] ?? 0);
        if ($earnedToday + $rule['points'] > $rule['daily']) return ['awarded' => false, 'points' => 0, 'reason' => 'daily_limit'];

        if ($rule['cooldown'] > 0) {
            $last = $events->where('user_id', $userId)->where('event_type', $eventType)->orderBy('created_at', 'DESC')->first();
            if ($last && strtotime($last['created_at']) > time() - $rule['cooldown']) return ['awarded' => false, 'points' => 0, 'reason' => 'cooldown'];
        }

        $db = db_connect(); $db->transBegin();
        try {
            if (! $events->insert(['user_id' => $userId, 'event_type' => $eventType, 'source_key' => $sourceKey, 'points' => $rule['points'], 'created_at' => date('Y-m-d H:i:s')])) throw new \RuntimeException('XP event could not be saved.');
            $db->query('UPDATE users SET experience_points = experience_points + ? WHERE id = ?', [$rule['points'], $userId]);
            if (! $db->transStatus()) throw new \RuntimeException('XP could not be updated.');
            $db->transCommit();
            if ((int) session()->get('user_id') === $userId) session()->set('experience_points', (int) session()->get('experience_points') + $rule['points']);
            return ['awarded' => true, 'points' => $rule['points'], 'reason' => null];
        } catch (Throwable $e) {
            $db->transRollback(); log_message('error', 'XP award failed: {message}', ['message' => $e->getMessage()]);
            return ['awarded' => false, 'points' => 0, 'reason' => 'error'];
        }
    }

    public static function summary(int $xp): array
    {
        $xp = max(0, $xp); $level = (int) floor(sqrt($xp / 100)) + 1;
        $current = 100 * (($level - 1) ** 2); $next = 100 * ($level ** 2);
        return ['xp' => $xp, 'level' => $level, 'current' => $current, 'next' => $next, 'progress' => (int) floor((($xp - $current) / max(1, $next - $current)) * 100)];
    }
}
