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

        return view('journal/index', [
            'title'   => $isAdmin ? 'Tüm Günlükler' : 'Günlüğüm',
            'entries' => (new JournalEntryModel())->getVisibleTo($userId, $isAdmin),
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

        return redirect()->to(site_url('journal'))->with('success', 'Günlük kaydı oluşturuldu.');
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

        return redirect()->to(site_url('journal/' . $id))->with('success', 'Günlük kaydı güncellendi.');
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
