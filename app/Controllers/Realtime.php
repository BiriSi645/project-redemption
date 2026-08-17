<?php

namespace App\Controllers;

use App\Libraries\RealtimeTokenService;
use RuntimeException;

class Realtime extends BaseController
{
    public function token()
    {
        $userId = (int) session()->get('user_id');
        if ($userId < 1 || ! session()->get('logged_in')) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Oturum bulunamadı.',
            ]);
        }

        try {
            $token = (new RealtimeTokenService())->issue(
                $userId,
                (string) session()->get('username'),
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
