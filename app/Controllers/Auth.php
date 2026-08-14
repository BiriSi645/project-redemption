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

        /*
        * Kullanıcıya göndereceğimiz gerçek token.
        *
        * 32 byte random veri -> 64 karakter hexadecimal string.
        */
        $verificationToken = bin2hex(random_bytes(32));

        /*
        * Gerçek tokenı veritabanında tutmuyoruz.
        * SHA-256 hash'ini saklıyoruz.
        */
        $verificationTokenHash = hash('sha256', $verificationToken);

        $userModel = new UserModel();

        $data = [
            'username'      => trim((string) $this->request->getPost('username')),
            'email'         => strtolower(trim((string) $this->request->getPost('email'))),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),

            'role'          => 'user',
            'theme'         => 'system',

            'email_verified_at' => null,

            'email_verification_token' => $verificationTokenHash,

            'email_verification_expires_at' => date(
                'Y-m-d H:i:s',
                strtotime('+30 minutes')
            ),
        ];

        if (! $userModel->insert($data)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('errors', $userModel->errors());
        }

        $userId = (int) $userModel->getInsertID();

        /*
        * Mailde bulunacak doğrulama bağlantısı.
        */
        $verificationUrl = site_url(
            'verify-email/' . $verificationToken
        );

        /*
        * CodeIgniter Email servisi.
        */
        $email = service('email');

        $email->setFrom(
            env('email.fromEmail'),
            env('email.fromName', 'Project Redemption')
        );

        $email->setTo($data['email']);

        $email->setSubject(
            'Project Redemption - E-posta Doğrulama'
        );

        $email->setMailType('html');

        $email->setMessage(
            '
            <h2>Project Redemption</h2>

            <p>
                Merhaba ' . esc($data['username']) . ',
            </p>

            <p>
                Project Redemption hesabını oluşturduk.
                Hesabını aktifleştirmek için aşağıdaki bağlantıya tıkla:
            </p>

            <p>
                <a href="' . esc($verificationUrl) . '">
                    E-posta adresimi doğrula
                </a>
            </p>

            <p>
                Bu bağlantı 30 dakika boyunca geçerlidir.
            </p>

            <p>
                Eğer bu hesabı sen oluşturmadıysan
                bu e-postayı görmezden gelebilirsin.
            </p>
            '
        );

        /*
        * Mail gönderilemezse kullanıcı DB'de oluşturulmuş olur
        * fakat doğrulanamaz.
        *
        * Şimdilik kullanıcıya bunu bildiriyoruz.
        * Sonra "maili tekrar gönder" özelliği ekleyeceğiz.
        */
        if (! $email->send(false)) {

            log_message(
                'error',
                'Kullanıcı #{id} için doğrulama e-postası gönderilemedi. {debug}',
                [
                    'id'    => $userId,
                    'debug' => $email->printDebugger(),
                ]
            );

            AuditLogger::record(
                $userId,
                'auth.register_email_failed',
                'Kullanıcı oluşturuldu ancak doğrulama e-postası gönderilemedi',
                'POST',
                'register',
                500
            );

            return redirect()
                ->to(site_url('login'))
                ->with(
                    'error',
                    'Hesabınız oluşturuldu fakat doğrulama e-postası gönderilemedi.'
                );
        }

        AuditLogger::record(
            $userId,
            'auth.register',
            'Yeni kullanıcı hesabı oluşturuldu ve doğrulama e-postası gönderildi',
            'POST',
            'register',
            201
        );

        return redirect()
            ->to(site_url('login'))
            ->with(
                'success',
                'Hesabınız oluşturuldu. E-posta adresinize gönderilen doğrulama bağlantısına tıklayın.'
            );
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
        
        if (empty($user['email_verified_at'])) {

            $expiresAt = $user['email_verification_expires_at'] ?? null;

            $canResend = empty($expiresAt)
                || strtotime($expiresAt) <= time();

            return redirect()
                ->back()
                ->withInput()
                ->with('verification_pending', [
                    'email'      => $user['email'],
                    'can_resend' => $canResend,
                    'expires_at' => $expiresAt,
                ]);
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
        (new UserModel())->skipValidation(true)->update((int) $user['id'], ['last_seen_at' => date('Y-m-d H:i:s')]);

        AuditLogger::record((int) $user['id'], 'auth.login', 'Kullanıcı giriş yaptı', 'POST', 'login', 200);

        return redirect()->to(site_url('dashboard'));
    }

    public function logout()
    {
        $userId = (int) session()->get('user_id');
        AuditLogger::record($userId, 'auth.logout', 'Kullanıcı çıkış yaptı', 'POST', 'logout', 200);
        (new UserModel())->skipValidation(true)->update($userId, ['last_seen_at' => null]);
        cache()->delete('presence_touch_' . $userId);
        session()->destroy();

        return redirect()->to(site_url('login'));
    }

    public function verifyEmail(string $token)
    {
        if ($token === '') {
            return redirect()
                ->to(site_url('login'))
                ->with('error', 'Geçersiz doğrulama bağlantısı.');
        }

        $tokenHash = hash('sha256', $token);

        $userModel = new UserModel();

        $user = $userModel
            ->where('email_verification_token', $tokenHash)
            ->first();

        if (! $user) {
            return redirect()
                ->to(site_url('login'))
                ->with(
                    'error',
                    'Doğrulama bağlantısı geçersiz veya daha önce kullanılmış.'
                );
        }

        if (! empty($user['email_verified_at'])) {
            return redirect()
                ->to(site_url('login'))
                ->with(
                    'success',
                    'E-posta adresiniz zaten doğrulanmış.'
                );
        }

        if (
            empty($user['email_verification_expires_at'])
            || strtotime($user['email_verification_expires_at']) < time()
        ) {
            return redirect()
                ->to(site_url('login'))
                ->with(
                    'error',
                    'Doğrulama bağlantısının süresi dolmuş.'
                );
        }

        $updated = $userModel
            ->skipValidation(true)
            ->update(
                $user['id'],
                [
                    'email_verified_at' => date('Y-m-d H:i:s'),
                    'email_verification_token' => null,
                    'email_verification_expires_at' => null,
                ]
            );

        if (! $updated) {
            log_message(
                'error',
                'Email verification update failed for user ' . $user['id']
            );

            return redirect()
                ->to(site_url('login'))
                ->with(
                    'error',
                    'E-posta doğrulanırken bir hata oluştu.'
                );
        }

        AuditLogger::record(
            (int) $user['id'],
            'auth.email_verified',
            'Kullanıcının e-posta adresi doğrulandı',
            'GET',
            'verify-email',
            200
        );

        return redirect()
            ->to(site_url('login'))
            ->with(
                'success',
                'E-posta adresiniz başarıyla doğrulandı. Şimdi giriş yapabilirsiniz.'
            );
    }


    public function resendVerification()
    {
        $emailAddress = strtolower(
            trim((string) $this->request->getPost('email'))
        );

        if ($emailAddress === '') {
            return redirect()
                ->to(site_url('login'))
                ->with(
                    'error',
                    'Geçersiz e-posta adresi.'
                );
        }

        $userModel = new UserModel();

        $user = $userModel
            ->where('email', $emailAddress)
            ->first();

        if (! $user) {
            return redirect()
                ->to(site_url('login'))
                ->with(
                    'error',
                    'Kullanıcı bulunamadı.'
                );
        }

        /*
        * Kullanıcı zaten doğrulanmışsa
        * tekrar mail göndermiyoruz.
        */
        if (! empty($user['email_verified_at'])) {
            return redirect()
                ->to(site_url('login'))
                ->with(
                    'success',
                    'Bu e-posta adresi zaten doğrulanmış. Giriş yapabilirsiniz.'
                );
        }

        /*
        * Eski link henüz geçerliyse
        * yeni link üretme.
        */
        if (
            ! empty($user['email_verification_expires_at'])
            && strtotime($user['email_verification_expires_at']) > time()
        ) {
            return redirect()
                ->to(site_url('login'))
                ->with(
                    'error',
                    'Mevcut doğrulama bağlantınız hâlâ geçerli. '
                    . 'Lütfen e-posta ve spam kutunuzu kontrol edin.'
                );
        }

        /*
        * Yeni doğrulama tokenı.
        */
        $verificationToken = bin2hex(
            random_bytes(32)
        );

        $verificationTokenHash = hash(
            'sha256',
            $verificationToken
        );

        /*
        * Database'e yeni token hash'i ve
        * yeni 30 dakikalık süre yaz.
        */
        $updated = $userModel
            ->skipValidation(true)
            ->update(
                $user['id'],
                [
                    'email_verification_token' =>
                        $verificationTokenHash,

                    'email_verification_expires_at' =>
                        date(
                            'Y-m-d H:i:s',
                            strtotime('+30 minutes')
                        ),
                ]
            );

        if (! $updated) {
            return redirect()
                ->to(site_url('login'))
                ->with(
                    'error',
                    'Yeni doğrulama bağlantısı oluşturulamadı.'
                );
        }

        /*
        * Yeni doğrulama URL'si.
        */
        $verificationUrl = site_url(
            'verify-email/' . $verificationToken
        );

        /*
        * Mail gönder.
        */
        $email = service('email');

        $email->setFrom(
            env('email.fromEmail'),
            env(
                'email.fromName',
                'Project Redemption'
            )
        );

        $email->setTo($user['email']);

        $email->setSubject(
            'Project Redemption - Yeni E-posta Doğrulama Bağlantısı'
        );

        $email->setMailType('html');

        $email->setMessage(
            '
            <h2>Project Redemption</h2>

            <p>
                Merhaba ' . esc($user['username']) . ',
            </p>

            <p>
                Yeni e-posta doğrulama bağlantınız hazır.
            </p>

            <p>
                <a href="' . esc($verificationUrl) . '">
                    E-posta adresimi doğrula
                </a>
            </p>

            <p>
                Bu bağlantı 30 dakika boyunca geçerlidir.
            </p>

            <p>
                Bu işlemi siz yapmadıysanız
                bu e-postayı görmezden gelebilirsiniz.
            </p>
            '
        );

        if (! $email->send(false)) {

            log_message(
                'error',
                'Doğrulama e-postası tekrar gönderilemedi. User ID: '
                . $user['id']
                . ' Debug: '
                . $email->printDebugger()
            );

            return redirect()
                ->to(site_url('login'))
                ->with(
                    'error',
                    'Doğrulama e-postası gönderilemedi.'
                );
        }

        AuditLogger::record(
            (int) $user['id'],
            'auth.verification_resent',
            'Yeni e-posta doğrulama bağlantısı gönderildi',
            'POST',
            'resend-verification',
            200
        );

        return redirect()
            ->to(site_url('login'))
            ->with(
                'success',
                'Yeni doğrulama bağlantısı e-posta adresinize gönderildi. '
                . 'Lütfen gelen kutunuzu ve spam klasörünüzü kontrol edin.'
            );
    }
}
