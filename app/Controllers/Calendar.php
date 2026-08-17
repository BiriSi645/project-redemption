<?php

namespace App\Controllers;

use App\Models\CalendarReminderModel;
use App\Models\JournalEntryModel;
use App\Models\NotificationModel;
use App\Models\TaskModel;
use DateTimeImmutable;
use DateTimeZone;

class Calendar extends BaseController
{
    public function index(): string
    {
        $month = (string) $this->request->getGet('month');
        $selectedDate = (string) $this->request->getGet('date');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $selectedDate);
            if ($selectedDateObject && $selectedDateObject->format('Y-m-d') === $selectedDate) {
                $month = $selectedDateObject->format('Y-m');
            } else {
                $selectedDate = '';
            }
        } else {
            $selectedDate = '';
        }

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $type = (string) $this->request->getGet('type');
        $taskStatus = (string) $this->request->getGet('status');
        $type = in_array($type, ['all', 'tasks', 'journals', 'reminders'], true) ? $type : 'all';
        $taskStatus = in_array($taskStatus, ['all', 'pending', 'completed'], true) ? $taskStatus : 'all';

        $firstDay = DateTimeImmutable::createFromFormat('!Y-m-d', $month . '-01') ?: new DateTimeImmutable('first day of this month');
        $lastDay  = $firstDay->modify('last day of this month');
        $userId   = (int) session()->get('user_id');

        $tasks = [];
        if (in_array($type, ['all', 'tasks'], true)) {
            $taskModel = new TaskModel();
            $taskModel->where('user_id', $userId)
                ->where('due_date >=', $firstDay->format('Y-m-d'))
                ->where('due_date <=', $lastDay->format('Y-m-d'));
            if ($taskStatus !== 'all') {
                $taskModel->where('status', $taskStatus);
            }
            $tasks = $taskModel->orderBy('due_time', 'ASC')->findAll();
        }

        $entries = ! in_array($type, ['all', 'journals'], true) ? [] : (new JournalEntryModel())
            ->where('user_id', $userId)
            ->where('entry_date >=', $firstDay->format('Y-m-d'))
            ->where('entry_date <=', $lastDay->format('Y-m-d'))
            ->orderBy('entry_date', 'ASC')
            ->findAll();

        $reminders = ! in_array($type, ['all', 'reminders'], true) ? [] : (new CalendarReminderModel())
            ->where('user_id', $userId)
            ->where('remind_at >=', $firstDay->format('Y-m-d 00:00:00'))
            ->where('remind_at <=', $lastDay->format('Y-m-d 23:59:59'))
            ->orderBy('remind_at', 'ASC')
            ->findAll();

        $events = [];
        foreach ($tasks as $task) {
            $events[$task['due_date']][] = ['type' => 'task', 'data' => $task];
        }
        foreach ($entries as $entry) {
            $events[$entry['entry_date']][] = ['type' => 'journal', 'data' => $entry];
        }
        foreach ($reminders as $reminder) {
            $events[substr($reminder['remind_at'], 0, 10)][] = ['type' => 'reminder', 'data' => $reminder];
        }

        return view('calendar/index', [
            'title'      => 'Takvim',
            'firstDay'   => $firstDay,
            'events'     => $events,
            'previous'   => $firstDay->modify('-1 month')->format('Y-m'),
            'next'       => $firstDay->modify('+1 month')->format('Y-m'),
            'monthLabel' => $this->monthLabel((int) $firstDay->format('n')) . ' ' . $firstDay->format('Y'),
            'activeType' => $type,
            'activeTaskStatus' => $taskStatus,
            'taskCount' => count($tasks),
            'journalCount' => count($entries),
            'reminderCount' => count($reminders),
            'selectedDate' => $selectedDate,
        ]);
    }

    public function storeReminder()
    {
        if (! $this->validate([
            'title' => 'required|min_length[2]|max_length[160]',
            'details' => 'permit_empty|max_length[1000]',
            'remind_at' => 'required|regex_match[/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $localValue = (string) $this->request->getPost('remind_at');
        $reminderTimezone = new DateTimeZone('Europe/Istanbul');
        $remindAt = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $localValue, $reminderTimezone);
        if (! $remindAt || $remindAt->format('Y-m-d\TH:i') !== $localValue) {
            return redirect()->back()->withInput()->with('error', 'Hatırlatıcı tarihi geçerli değil.');
        }
        if ($remindAt->getTimestamp() <= (new DateTimeImmutable('now', $reminderTimezone))->getTimestamp()) {
            return redirect()->back()->withInput()->with('error', 'Hatırlatıcı zamanı gelecekte olmalıdır.');
        }

        (new CalendarReminderModel())->insert([
            'user_id' => (int) session()->get('user_id'),
            'title' => trim((string) $this->request->getPost('title')),
            'details' => trim((string) $this->request->getPost('details')) ?: null,
            'remind_at' => $remindAt->format('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('calendar') . '?date=' . $remindAt->format('Y-m-d'))
            ->with('success', 'Hatırlatıcı oluşturuldu.');
    }

    public function deleteReminder(int $id)
    {
        $userId = (int) session()->get('user_id');
        $model = new CalendarReminderModel();
        $reminder = $model->where('user_id', $userId)->find($id);
        if (! $reminder) {
            return redirect()->back()->with('error', 'Hatırlatıcı bulunamadı.');
        }

        $model->delete($id);
        (new NotificationModel())->where('user_id', $userId)
            ->like('notification_key', 'calendar_reminder:' . $id . ':', 'after')
            ->delete();

        return redirect()->back()->with('success', 'Hatırlatıcı silindi.');
    }

    private function monthLabel(int $month): string
    {
        return [1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'][$month];
    }
}
