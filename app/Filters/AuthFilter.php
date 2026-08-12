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

        $user = (new UserModel())->select('id,is_active,role')->find((int) session()->get('user_id'));
        if (! $user || (int) ($user['is_active'] ?? 1) !== 1) {
            session()->destroy();
            return redirect()->to(site_url('login'))->with('errors', ['auth'=>'Hesabınız aktif değil.']);
        }

        if (session()->get('role') !== $user['role']) {
            session()->set('role', $user['role']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Bu filtre yalnızca istek öncesi oturum kontrolü yapar.
    }
}
