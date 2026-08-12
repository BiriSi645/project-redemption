<?php

namespace App\Controllers;

use App\Models\NoteModel;
use App\Models\NoteCommentModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Notes extends BaseController
{
    public function index()
    {
        $userId  = (int) session()->get('user_id');
        $isAdmin = session()->get('role') === 'admin';
        $search   = trim((string) $this->request->getGet('q'));
        $category = trim((string) $this->request->getGet('category'));
        $scope    = (string) $this->request->getGet('scope');
        $scope    = in_array($scope, ['all', 'public', 'mine'], true) ? $scope : 'all';
        $noteModel = new NoteModel();

        return view('notes/index', [
            'title'   => $isAdmin ? 'Tüm Notlar' : 'Notlar',
            'notes'   => $noteModel->getVisibleTo($userId, $isAdmin, $search, $category, $scope, 6),
            'pager'   => $noteModel->pager,
            'categories' => $noteModel->categoriesVisibleTo($userId, $isAdmin, $scope),
            'search' => $search,
            'activeCategory' => $category,
            'activeScope' => $scope,
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
            'comments'  => (new NoteCommentModel())->getForNote($id),
            'userId'    => (int) session()->get('user_id'),
            'isAdmin'   => session()->get('role') === 'admin',
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
            'category'  => $this->cleanCategory(),
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
            'category'  => $this->cleanCategory(),
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

    public function storeComment(int $noteId)
    {
        $this->findVisibleNote($noteId);
        $commentModel = new NoteCommentModel();

        if (! $commentModel->insert([
            'note_id' => $noteId,
            'user_id' => (int) session()->get('user_id'),
            'content' => trim((string) $this->request->getPost('content')),
        ])) {
            return redirect()->to(site_url('notes/' . $noteId))->withInput()->with('errors', $commentModel->errors());
        }

        return redirect()->to(site_url('notes/' . $noteId) . '#comments')->with('success', 'Yorumunuz eklendi.');
    }

    public function deleteComment(int $noteId, int $commentId)
    {
        $note = $this->findVisibleNote($noteId);
        $commentModel = new NoteCommentModel();
        $comment = $commentModel->find($commentId);
        $userId = (int) session()->get('user_id');
        $canDelete = $comment
            && (int) $comment['note_id'] === $noteId
            && (
                (int) $comment['user_id'] === $userId
                || (int) $note['user_id'] === $userId
                || session()->get('role') === 'admin'
            );

        if (! $canDelete) {
            throw PageNotFoundException::forPageNotFound('Yorum bulunamadı.');
        }

        $commentModel->delete($commentId);

        return redirect()->to(site_url('notes/' . $noteId) . '#comments')->with('success', 'Yorum silindi.');
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

    private function cleanCategory(): string
    {
        $category = trim((string) $this->request->getPost('category'));

        return $category === '' ? 'Genel' : $category;
    }
}
