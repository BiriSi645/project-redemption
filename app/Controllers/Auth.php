<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Libraries\AuditLogger;

class Auth extends BaseController
{
    public function index()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(site_url('dashboard'));
        }

        return redirect()->to(site_url('login'));
    }

    public function register()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(site_url('dashboard'));
        }

        return view('auth/register');
    }

    public function storeRegister()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(site_url('dashboard'));
        }

        $password        = (string) $this->request->getPost('password');
        $passwordConfirm = (string) $this->request->getPost('password_confirm');

        if (strlen($password) < 6) {
            return redirect()->back()->withInput()->with('errors', [
                'password' => 'Şifre en az 6 karakter olmalıdır.',
            ]);
        }

        if ($password !== $passwordConfirm) {
            return redirect()->back()->withInput()->with('errors', [
                'password_confirm' => 'Şifreler eşleşmiyor.',
            ]);
        }

        $userModel = new UserModel();
        $data = [
            'username'      => trim((string) $this->request->getPost('username')),
            'email'         => strtolower(trim((string) $this->request->getPost('email'))),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => 'user',
            'theme'         => 'system',
        ];

        if (! $userModel->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $userModel->errors());
        }

        AuditLogger::record((int) $userModel->getInsertID(), 'auth.register', 'Yeni kullanıcı hesabı oluşturuldu', 'POST', 'register', 201);

        return redirect()->to(site_url('login'))
            ->with('success', 'Hesabınız oluşturuldu. Şimdi giriş yapabilirsiniz.');
    }

    public function login()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(site_url('dashboard'));
        }

        return view('auth/login');
    }

    public function storeLogin()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(site_url('dashboard'));
        }

        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $user     = (new UserModel())->where('email', $email)->first();

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            AuditLogger::record($user ? (int) $user['id'] : null, 'auth.login_failed', 'Başarısız giriş denemesi', 'POST', 'login', 401);
            return redirect()->back()->withInput()->with('errors', [
                'login' => 'E-posta veya şifre hatalı.',
            ]);
        }

        if ((int) ($user['is_active'] ?? 1) !== 1) {
            AuditLogger::record((int) $user['id'], 'auth.login_blocked', 'Pasif hesaba giriş denemesi', 'POST', 'login', 403);
            return redirect()->back()->withInput()->with('errors', ['login'=>'Hesabınız devre dışı bırakılmış.']);
        }

        session()->regenerate(true);
        session()->set([
            'user_id'   => (int) $user['id'],
            'username'  => $user['username'],
            'email'     => $user['email'],
            'role'      => $user['role'] ?? 'user',
            'theme'     => $user['theme'] ?? 'system',
            'notifications_enabled' => (int) ($user['notifications_enabled'] ?? 0),
            'logged_in' => true,
        ]);

        AuditLogger::record((int) $user['id'], 'auth.login', 'Kullanıcı giriş yaptı', 'POST', 'login', 200);

        return redirect()->to(site_url('dashboard'));
    }

    public function logout()
    {
        AuditLogger::record((int) session()->get('user_id'), 'auth.logout', 'Kullanıcı çıkış yaptı', 'POST', 'logout', 200);
        session()->destroy();

        return redirect()->to(site_url('login'));
    }
}
