<?php

namespace App\Controllers;

use App\Models\NoteModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Users extends BaseController
{
    public function updateProfile()
    {
        $userId = (int) session()->get('user_id');
        $username = trim((string) $this->request->getPost('username'));
        $bio = trim((string) $this->request->getPost('bio'));
        $rules = [
            'username' => "required|min_length[3]|max_length[100]|is_unique[users.username,id,{$userId}]",
            'bio' => 'permit_empty|max_length[300]',
        ];

        if (! $this->validateData(['username' => $username, 'bio' => $bio], $rules)) {
            return redirect()->to(site_url('users/' . $userId))
                ->withInput()->with('errors', $this->validator->getErrors());
        }

        (new UserModel())->skipValidation(true)->update($userId, [
            'username' => $username,
            'bio' => $bio === '' ? null : $bio,
        ]);
        session()->set('username', $username);

        return redirect()->to(site_url('users/' . $userId))->with('success', 'Profiliniz güncellendi.');
    }

    public function show(int $id)
    {
        $user = (new UserModel())->select('id, username, bio, role, created_at')->find($id);
        if (! $user) {
            throw PageNotFoundException::forPageNotFound('Kullanıcı bulunamadı.');
        }

        return $this->profile($user);
    }

    public function byUsername(?string $username = null)
    {
        $username = $username === null
            ? trim((string) $this->request->getGet('username'))
            : rawurldecode($username);

        if ($username === '') {
            throw PageNotFoundException::forPageNotFound('Kullanıcı bulunamadı.');
        }

        $user = (new UserModel())->select('id, username, bio, role, created_at')->where('username', $username)->first();
        if (! $user) {
            throw PageNotFoundException::forPageNotFound('Kullanıcı bulunamadı.');
        }

        return $this->profile($user);
    }

    private function profile(array $user)
    {
        $noteModel = new NoteModel();
        $notes = $noteModel->select('notes.*, users.username AS owner_name')
            ->join('users', 'users.id = notes.user_id', 'left')
            ->where('notes.user_id', $user['id'])
            ->where('notes.is_public', 1)
            ->orderBy('notes.created_at', 'DESC')
            ->paginate(6, 'profile_notes');

        return view('users/show', [
            'title' => $user['username'] . ' profili',
            'profileUser' => $user,
            'isOwnProfile' => (int) $user['id'] === (int) session()->get('user_id'),
            'notes' => $notes,
            'pager' => $noteModel->pager,
        ]);
    }
}
