<?php

namespace App\Controllers;

use App\Libraries\RealtimeTokenService;
use RuntimeException;

class Realtime extends BaseController
{
    public function token()
    {
        $userId = (int) session()->get('user_id');
        $loggedIn = (bool) session()->get('logged_in');
        $username = (string) session()->get('username');

        session_write_close();

        if ($userId < 1 || ! $loggedIn) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Oturum bulunamadı.',
            ]);
        }

        try {
            $token = (new RealtimeTokenService())->issue(
                $userId,
                $username,
                120
            );
        } catch (RuntimeException $exception) {
            log_message('error', 'Realtime token üretilemedi: ' . $exception->getMessage());

            return $this->response->setStatusCode(503)->setJSON([
                'success' => false,
                'message' => 'Realtime bağlantısı şu anda kullanılamıyor.',
            ]);
        }

        return $this->response
            ->setHeader('Cache-Control', 'no-store, private')
            ->setJSON([
                'success' => true,
                'token' => $token,
                'expiresIn' => 120,
            ]);
    }
}
