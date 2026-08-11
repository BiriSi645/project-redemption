<?php

namespace App\Controllers;

use App\Models\NoteModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Notes extends BaseController
{
    public function index()
    {
        $userId  = (int) session()->get('user_id');
        $isAdmin = session()->get('role') === 'admin';

        return view('notes/index', [
            'title'   => $isAdmin ? 'Tüm Notlar' : 'Notlar',
            'notes'   => (new NoteModel())->getVisibleTo($userId, $isAdmin),
            'userId'  => $userId,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function show(int $id)
    {
        $note = $this->findVisibleNote($id);

        return view('notes/show', [
            'title'     => $note['title'],
            'note'      => $note,
            'isOwner'   => (int) $note['user_id'] === (int) session()->get('user_id'),
            'canDelete' => (int) $note['user_id'] === (int) session()->get('user_id')
                || session()->get('role') === 'admin',
        ]);
    }

    public function create()
    {
        return view('notes/create', ['title' => 'Yeni Not']);
    }

    public function store()
    {
        $noteModel = new NoteModel();
        $data = [
            'user_id'   => (int) session()->get('user_id'),
            'title'     => trim((string) $this->request->getPost('title')),
            'content'   => trim((string) $this->request->getPost('content')),
            'is_public' => $this->request->getPost('is_public') === '1' ? 1 : 0,
        ];

        if (! $noteModel->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $noteModel->errors());
        }

        return redirect()->to(site_url('notes'))->with('success', 'Not oluşturuldu.');
    }

    public function edit(int $id)
    {
        return view('notes/edit', [
            'title' => 'Notu Düzenle',
            'note'  => $this->findOwnedNote($id),
        ]);
    }

    public function update(int $id)
    {
        $this->findOwnedNote($id);
        $noteModel = new NoteModel();
        $data = [
            'title'     => trim((string) $this->request->getPost('title')),
            'content'   => trim((string) $this->request->getPost('content')),
            'is_public' => $this->request->getPost('is_public') === '1' ? 1 : 0,
        ];

        if (! $noteModel->update($id, $data)) {
            return redirect()->back()->withInput()->with('errors', $noteModel->errors());
        }

        return redirect()->to(site_url('notes'))->with('success', 'Not güncellendi.');
    }

    public function delete(int $id)
    {
        $this->findDeletableNote($id);
        (new NoteModel())->delete($id);

        return redirect()->to(site_url('notes'))->with('success', 'Not silindi.');
    }

    private function findVisibleNote(int $id): array
    {
        $note = (new NoteModel())
            ->select('notes.*, users.username AS owner_name')
            ->join('users', 'users.id = notes.user_id', 'left')
            ->find($id);

        $canView = $note
            && (
                (int) $note['user_id'] === (int) session()->get('user_id')
                || (int) $note['is_public'] === 1
                || session()->get('role') === 'admin'
            );

        if (! $canView) {
            throw PageNotFoundException::forPageNotFound('Not bulunamadı.');
        }

        return $note;
    }

    private function findOwnedNote(int $id): array
    {
        $note = (new NoteModel())->find($id);

        if (! $note || (int) $note['user_id'] !== (int) session()->get('user_id')) {
            throw PageNotFoundException::forPageNotFound('Not bulunamadı.');
        }

        return $note;
    }

    private function findDeletableNote(int $id): array
    {
        $note = (new NoteModel())->find($id);

        $canDelete = $note
            && (
                (int) $note['user_id'] === (int) session()->get('user_id')
                || session()->get('role') === 'admin'
            );

        if (! $canDelete) {
            throw PageNotFoundException::forPageNotFound('Not bulunamadı.');
        }

        return $note;
    }
}
