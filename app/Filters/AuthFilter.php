<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('logged_in') || ! session()->get('user_id')) {
            return redirect()
                ->to(site_url('login'))
                ->with('errors', ['auth' => 'Bu sayfayı görüntülemek için giriş yapmalısınız.']);
        }

        $userId = (int) session()->get('user_id');
        $user = (new UserModel())
            ->select('id,is_active,role,auth_version')
            ->find($userId);
        if (! $user || (int) ($user['is_active'] ?? 1) !== 1) {
            session()->destroy();

            return redirect()->to(site_url('login'));
        }

        if ((int) session()->get('auth_version') !== (int) ($user['auth_version'] ?? 1)) {
            session()->destroy();

            return redirect()->to(site_url('login'));
        }

        if (session()->get('role') !== $user['role']) {
            session()->set('role', $user['role']);
        }

        $presenceKey = 'presence_touch_' . $userId;
        if (! cache()->get($presenceKey)) {
            (new UserModel())->skipValidation(true)->update($userId, ['last_seen_at' => date('Y-m-d H:i:s')]);
            cache()->save($presenceKey, '1', 60);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Bu filtre yalnızca istek öncesi oturum kontrolü yapar.
    }
}
