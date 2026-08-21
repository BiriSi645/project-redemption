<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    private const READ_ONLY_SESSION_PATHS = [
        'messages/preview',
        'notifications/preview',
        'system/active-users',
        'system/heartbeat',
        'system/live-updates',
        'system/realtime-token',
    ];

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
            return redirect()->to(site_url('login'))->with('errors', ['auth'=>'Hesabınız aktif değil.']);
        }

        if ((int) session()->get('auth_version') !== (int) ($user['auth_version'] ?? 1)) {
            session()->destroy();

            return redirect()
                ->to(site_url('login'))
                ->with('errors', ['auth' => 'Şifreniz değiştirildiği için yeniden giriş yapmalısınız.']);
        }

        if (session()->get('role') !== $user['role']) {
            session()->set('role', $user['role']);
        }

        // Database sessions use a MySQL advisory lock. The dashboard starts
        // several read-only requests at once, so keeping that lock until each
        // controller finishes serializes the requests and can exhaust/timeout
        // serverless Aiven connections. The session data remains readable
        // after close(); only the database lock is released early.
        $path = trim($request->getUri()->getPath(), '/');
        $path = preg_replace('#^index\.php/?#', '', $path) ?? $path;
        if (
            strtoupper($request->getMethod()) === 'GET'
            && in_array($path, self::READ_ONLY_SESSION_PATHS, true)
        ) {
            session()->close();
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
