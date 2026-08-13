<?php

namespace App\Controllers;

use App\Models\NoteModel;
use App\Models\TaskModel;
use App\Models\JournalEntryModel;
use App\Models\HabitModel;
use DateTimeImmutable;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $userId  = (int) session()->get('user_id');
        $isAdmin = session()->get('role') === 'admin';
        $noteModel = new NoteModel();
        $noteSummary = $noteModel->dashboardSummary($userId, $isAdmin);
        $taskSummary = (new TaskModel())->dashboardSummary($userId);

        $journalEntryCount = (new JournalEntryModel())
            ->where('user_id', $userId)
            ->countAllResults();

        $reminderTasks = (new TaskModel())
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->where('due_date IS NOT NULL', null, false)
            ->where('due_date <=', date('Y-m-d', strtotime('+7 days')))
            ->orderBy('due_date', 'ASC')
            ->orderBy('due_time', 'ASC')
            ->limit(10)
            ->findAll();

        $calendarFirstDay = new DateTimeImmutable('first day of this month');
        $calendarLastDay = $calendarFirstDay->modify('last day of this month');
        $calendarEvents = [];
        $calendarTasks = (new TaskModel())
            ->select('due_date')
            ->where('user_id', $userId)
            ->where('due_date >=', $calendarFirstDay->format('Y-m-d'))
            ->where('due_date <=', $calendarLastDay->format('Y-m-d'))
            ->findAll();
        $calendarEntries = (new JournalEntryModel())
            ->select('entry_date')
            ->where('user_id', $userId)
            ->where('entry_date >=', $calendarFirstDay->format('Y-m-d'))
            ->where('entry_date <=', $calendarLastDay->format('Y-m-d'))
            ->findAll();

        foreach ($calendarTasks as $task) {
            $calendarEvents[$task['due_date']]['tasks'] = ($calendarEvents[$task['due_date']]['tasks'] ?? 0) + 1;
        }
        foreach ($calendarEntries as $entry) {
            $calendarEvents[$entry['entry_date']]['journals'] = ($calendarEvents[$entry['entry_date']]['journals'] ?? 0) + 1;
        }

        $dashboardHabits = (new HabitModel())->getForUserWithCurrentStatus($userId, true, 5);

        return view('dashboard/index', [
            'title'           => 'Ana Sayfa',
            'latestNotes'     => $noteModel->latestVisibleTo($userId, $isAdmin, 5),
            'ownNoteCount'    => $noteSummary['own'],
            'publicNoteCount' => $noteSummary['public'],
            'visibleCount'    => $noteSummary['visible'],
            'pendingTaskCount' => $taskSummary['pending'],
            'dueTodayCount'    => $taskSummary['dueToday'],
            'journalEntryCount' => $journalEntryCount,
            'reminderTasks'     => $reminderTasks,
            'calendarFirstDay'  => $calendarFirstDay,
            'calendarEvents'    => $calendarEvents,
            'dashboardHabits'   => $dashboardHabits,
            'userId'          => $userId,
            'isAdmin'         => $isAdmin,
        ]);
    }
}
