<?php

namespace App\Controllers;

use App\Models\JournalEntryModel;
use App\Models\TaskModel;
use DateTimeImmutable;

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
        $type = in_array($type, ['all', 'tasks', 'journals'], true) ? $type : 'all';
        $taskStatus = in_array($taskStatus, ['all', 'pending', 'completed'], true) ? $taskStatus : 'all';

        $firstDay = DateTimeImmutable::createFromFormat('!Y-m-d', $month . '-01') ?: new DateTimeImmutable('first day of this month');
        $lastDay  = $firstDay->modify('last day of this month');
        $userId   = (int) session()->get('user_id');

        $tasks = [];
        if ($type !== 'journals') {
            $taskModel = new TaskModel();
            $taskModel->where('user_id', $userId)
                ->where('due_date >=', $firstDay->format('Y-m-d'))
                ->where('due_date <=', $lastDay->format('Y-m-d'));
            if ($taskStatus !== 'all') {
                $taskModel->where('status', $taskStatus);
            }
            $tasks = $taskModel->orderBy('due_time', 'ASC')->findAll();
        }

        $entries = $type === 'tasks' ? [] : (new JournalEntryModel())
            ->where('user_id', $userId)
            ->where('entry_date >=', $firstDay->format('Y-m-d'))
            ->where('entry_date <=', $lastDay->format('Y-m-d'))
            ->orderBy('entry_date', 'ASC')
            ->findAll();

        $events = [];
        foreach ($tasks as $task) {
            $events[$task['due_date']][] = ['type' => 'task', 'data' => $task];
        }
        foreach ($entries as $entry) {
            $events[$entry['entry_date']][] = ['type' => 'journal', 'data' => $entry];
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
            'selectedDate' => $selectedDate,
        ]);
    }

    private function monthLabel(int $month): string
    {
        return [1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'][$month];
    }
}
