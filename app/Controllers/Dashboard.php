<?php

namespace App\Controllers;

use App\Models\NoteModel;
use App\Models\TaskModel;
use App\Models\JournalEntryModel;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $userId  = (int) session()->get('user_id');
        $isAdmin = session()->get('role') === 'admin';
        $visibleNotes = (new NoteModel())->getVisibleTo($userId, $isAdmin);

        $ownNotes = array_filter(
            $visibleNotes,
            static fn (array $note): bool => (int) $note['user_id'] === $userId
        );

        $publicNotes = array_filter(
            $visibleNotes,
            static fn (array $note): bool => (int) $note['is_public'] === 1
        );

        $pendingTaskCount = (new TaskModel())
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->countAllResults();

        $dueTodayCount = (new TaskModel())
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->where('due_date', date('Y-m-d'))
            ->countAllResults();

        $journalEntryCount = (new JournalEntryModel())
            ->where('user_id', $userId)
            ->countAllResults();

        return view('dashboard/index', [
            'title'           => 'Ana Sayfa',
            'latestNotes'     => array_slice($visibleNotes, 0, 5),
            'ownNoteCount'    => count($ownNotes),
            'publicNoteCount' => count($publicNotes),
            'visibleCount'    => count($visibleNotes),
            'pendingTaskCount' => $pendingTaskCount,
            'dueTodayCount'    => $dueTodayCount,
            'journalEntryCount' => $journalEntryCount,
            'userId'          => $userId,
            'isAdmin'         => $isAdmin,
        ]);
    }
}
