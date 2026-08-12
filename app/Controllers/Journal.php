<?php

namespace App\Controllers;

use App\Models\JournalEntryModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Journal extends BaseController
{
    public function index(): string
    {
        $userId  = (int) session()->get('user_id');
        $isAdmin = session()->get('role') === 'admin';
        $search   = trim((string) $this->request->getGet('q'));
        $mood     = (string) $this->request->getGet('mood');
        $scope    = $isAdmin && $this->request->getGet('scope') === 'all' ? 'all' : 'mine';
        $dateFrom = (string) $this->request->getGet('date_from');
        $dateTo   = (string) $this->request->getGet('date_to');
        $dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : '';
        $dateTo   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : '';
        $journalModel = new JournalEntryModel();

        return view('journal/index', [
            'title'   => $isAdmin && $scope === 'all' ? 'Tüm Günlükler' : 'Günlüğüm',
            'entries' => $journalModel->getVisibleTo($userId, $isAdmin, $search, $mood, $dateFrom, $dateTo, $scope, 8),
            'pager' => $journalModel->pager,
            'search' => $search,
            'activeMood' => $mood,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'activeScope' => $scope,
            'userId'  => $userId,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function show(int $id): string
    {
        $entry = $this->findVisibleEntry($id);

        return view('journal/show', [
            'title'   => $entry['title'],
            'entry'   => $entry,
            'isOwner' => (int) $entry['user_id'] === (int) session()->get('user_id'),
        ]);
    }

    public function create(): string
    {
        return view('journal/create', ['title' => 'Yeni Günlük Kaydı']);
    }

    public function store()
    {
        $entryModel = new JournalEntryModel();
        $data = $this->entryData();
        $data['user_id'] = (int) session()->get('user_id');

        if (! $entryModel->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $entryModel->errors());
        }

        return redirect()->to(site_url('journal'))
            ->with('success', 'Günlük kaydı oluşturuldu.')
            ->with('clearJournalDraft', 'project-redemption:journal-draft:' . (int) session()->get('user_id') . ':new');
    }

    public function edit(int $id): string
    {
        return view('journal/edit', [
            'title' => 'Günlük Kaydını Düzenle',
            'entry' => $this->findOwnedEntry($id),
        ]);
    }

    public function update(int $id)
    {
        $this->findOwnedEntry($id);
        $entryModel = new JournalEntryModel();

        if (! $entryModel->update($id, $this->entryData())) {
            return redirect()->back()->withInput()->with('errors', $entryModel->errors());
        }

        return redirect()->to(site_url('journal/' . $id))
            ->with('success', 'Günlük kaydı güncellendi.')
            ->with('clearJournalDraft', 'project-redemption:journal-draft:' . (int) session()->get('user_id') . ':' . $id);
    }

    public function delete(int $id)
    {
        $this->findOwnedEntry($id);
        (new JournalEntryModel())->delete($id);

        return redirect()->to(site_url('journal'))->with('success', 'Günlük kaydı silindi.');
    }

    private function entryData(): array
    {
        return [
            'entry_date' => trim((string) $this->request->getPost('entry_date')),
            'title'      => trim((string) $this->request->getPost('title')),
            'content'    => trim((string) $this->request->getPost('content')),
            'mood'       => (string) $this->request->getPost('mood'),
        ];
    }

    private function findVisibleEntry(int $id): array
    {
        $entry = (new JournalEntryModel())
            ->select('journal_entries.*, users.username AS owner_name')
            ->join('users', 'users.id = journal_entries.user_id', 'left')
            ->find($id);

        $canView = $entry
            && (
                (int) $entry['user_id'] === (int) session()->get('user_id')
                || session()->get('role') === 'admin'
            );

        if (! $canView) {
            throw PageNotFoundException::forPageNotFound('Günlük kaydı bulunamadı.');
        }

        return $entry;
    }

    private function findOwnedEntry(int $id): array
    {
        $entry = (new JournalEntryModel())->find($id);

        if (! $entry || (int) $entry['user_id'] !== (int) session()->get('user_id')) {
            throw PageNotFoundException::forPageNotFound('Günlük kaydı bulunamadı.');
        }

        return $entry;
    }
}
