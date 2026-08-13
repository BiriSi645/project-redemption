<?php

namespace App\Libraries;

use App\Models\NotificationModel;
use App\Models\TaskModel;

class TaskReminderService
{
    public function createDueSoonNotifications(int $userId): void
    {
        $cacheKey = 'task_reminders_checked_' . $userId;
        if (cache()->get($cacheKey)) {
            return;
        }

        $now = time();
        $until = $now + 86400;
        $tasks = (new TaskModel())
            ->select('id, title, due_date, due_time')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->where('due_date IS NOT NULL', null, false)
            ->where('due_date >=', date('Y-m-d', $now))
            ->where('due_date <=', date('Y-m-d', $until))
            ->findAll();
        $model = new NotificationModel();

        foreach ($tasks as $task) {
            $deadline = strtotime($task['due_date'] . ' ' . ($task['due_time'] ?: '23:59:59'));
            if ($deadline === false || $deadline < $now || $deadline > $until) {
                continue;
            }

            $key = 'task_due:' . $task['id'] . ':' . date('YmdHi', $deadline);
            if ($model->where('notification_key', $key)->first()) {
                continue;
            }

            $remainingMinutes = max(1, (int) ceil(($deadline - $now) / 60));
            $remaining = $remainingMinutes < 60
                ? $remainingMinutes . ' dakika'
                : ((int) ceil($remainingMinutes / 60)) . ' saat';

            $model->insert([
                'user_id' => $userId,
                'task_id' => (int) $task['id'],
                'type' => 'task_due',
                'message' => '“' . mb_strimwidth($task['title'], 0, 100, '…') . '” görevinin bitmesine yaklaşık ' . $remaining . ' kaldı.',
                'target_path' => 'tasks/' . $task['id'] . '/edit',
                'notification_key' => $key,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        cache()->save($cacheKey, '1', 60);
    }
}
