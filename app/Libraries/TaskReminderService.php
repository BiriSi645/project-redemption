<?php

namespace App\Libraries;

use App\Models\CalendarReminderModel;
use App\Models\NotificationModel;
use App\Models\TaskModel;
use DateTimeImmutable;
use DateTimeZone;

class TaskReminderService
{
    private const DUE_SOON_SECONDS = 86400;

    public function createDueSoonNotifications(int $userId): void
    {
        $cacheKey = 'task_reminders_checked_' . $userId;
        if (cache()->get($cacheKey)) return;

        $timezone = new DateTimeZone('Europe/Istanbul');
        $localNow = new DateTimeImmutable('now', $timezone);
        $now = $localNow->getTimestamp();
        $tasks = (new TaskModel())
            ->select('id, title, due_date, due_time')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->where('due_date IS NOT NULL', null, false)
            ->where('due_date <=', $localNow->modify('+1 day')->format('Y-m-d'))
            ->orderBy('due_date', 'DESC')
            ->orderBy('due_time', 'DESC')
            ->findAll();

        $notifications = new NotificationModel();
        foreach ($tasks as $task) {
            $deadlineObject = TaskDeadline::fromDatabase($task['due_date'], $task['due_time'], $timezone);
            if (! $deadlineObject) continue;
            $deadline = $deadlineObject->getTimestamp();
            if ($deadline > $now + self::DUE_SOON_SECONDS) continue;

            $overdue = $deadline < $now;
            $type = $overdue ? 'task_overdue' : 'task_due';
            $key = $type . ':' . $task['id'] . ':' . $deadlineObject->format('YmdHi');
            if ($notifications->where('notification_key', $key)->first()) continue;

            $message = $overdue
                ? $this->overdueMessage($task['title'], $now - $deadline)
                : $this->dueSoonMessage($task['title'], $deadline - $now);

            $notifications->insert([
                'user_id' => $userId,
                'task_id' => (int) $task['id'],
                'type' => $type,
                'message' => $message,
                'target_path' => 'tasks/' . $task['id'] . '/edit',
                'notification_key' => $key,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $reminders = (new CalendarReminderModel())
            ->select('id, title, remind_at')
            ->where('user_id', $userId)
            ->where('remind_at >=', $localNow->modify('-1 day')->format('Y-m-d H:i:s'))
            ->where('remind_at <=', $localNow->modify('+1 hour')->format('Y-m-d H:i:s'))
            ->orderBy('remind_at', 'ASC')
            ->findAll();

        foreach ($reminders as $reminder) {
            $reminderDateTime = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $reminder['remind_at'], $timezone);
            if (! $reminderDateTime) continue;
            $reminderTime = $reminderDateTime->getTimestamp();

            $key = 'calendar_reminder:' . $reminder['id'] . ':' . $reminderDateTime->format('YmdHi');
            if ($notifications->where('notification_key', $key)->first()) continue;

            $remainingSeconds = $reminderTime - $now;
            $message = $remainingSeconds <= 0
                ? '“' . mb_strimwidth($reminder['title'], 0, 100, '…') . '” hatırlatıcısının zamanı geldi.'
                : '“' . mb_strimwidth($reminder['title'], 0, 100, '…') . '” hatırlatıcısına ' . max(1, (int) ceil($remainingSeconds / 60)) . ' dakika kaldı.';

            $notifications->insert([
                'user_id' => $userId,
                'type' => 'calendar_reminder',
                'message' => $message,
                'target_path' => 'calendar',
                'notification_key' => $key,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        cache()->save($cacheKey, '1', 60);
    }

    private function dueSoonMessage(string $title, int $remainingSeconds): string
    {
        $minutes = max(1, (int) ceil($remainingSeconds / 60));
        $remaining = $minutes < 60 ? $minutes . ' dakika' : ((int) ceil($minutes / 60)) . ' saat';
        return '“' . mb_strimwidth($title, 0, 100, '…') . '” görevinin bitmesine yaklaşık ' . $remaining . ' kaldı.';
    }

    private function overdueMessage(string $title, int $overdueSeconds): string
    {
        $hours = max(1, (int) floor($overdueSeconds / 3600));
        $elapsed = $hours < 24 ? $hours . ' saat' : ((int) floor($hours / 24)) . ' gün';
        return '“' . mb_strimwidth($title, 0, 100, '…') . '” görevinin süresi ' . $elapsed . ' önce geçti.';
    }
}
