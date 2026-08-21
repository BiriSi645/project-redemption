<?php

namespace App\Controllers;

class Presence extends BaseController
{
    public function heartbeat()
    {
        session_write_close();

        return $this->response->setStatusCode(204);
    }

    public function activeUsers()
    {
        session_write_close();

        $users = (new \App\Models\UserModel())->activeUsers();
        return $this->response->setJSON([
            'users' => array_map(static fn (array $user): array => [
                'id' => (int) $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'profileUrl' => site_url('users/' . $user['id']),
            ], $users),
        ]);
    }
}
