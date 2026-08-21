<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Libraries\AuditLogger;
use App\Libraries\AuthRateLimiter;
use App\Libraries\DatabaseAuthRateLimitStore;
use App\Libraries\PasswordPolicy;

class Auth extends BaseController
{
    private const EMAIL_VERIFICATION_TTL_SECONDS = 600;
    private const EMAIL_VERIFICATION_RESEND_COOLDOWN_SECONDS = 60;
    private const EMAIL_VERIFICATION_MAX_ATTEMPTS = 5;
    private const AUTH_RATE_WINDOW_SECONDS = 300;
    private const REGISTER_RATE_MAX_ATTEMPTS = 5;
    public function index()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(site_url('dashboard'));
        }

        return redirect()->to(site_url('login'));
    }

    public function forgotPassword()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(site_url('dashboard'));
        }

        return view('auth/forgot_password');
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

        if ($this->rateLimited('register', self::REGISTER_RATE_MAX_ATTEMPTS)) {
            AuditLogger::record(
                null,
                'auth.register_rate_limited',
                'IP adresi için kayıt oluşturma sınırı aşıldı',
                'POST',
                'register',
                429
            );

            return redirect()
                ->back()
                ->withInput()
                ->with('errors', [
                    'register' => 'Çok fazla kayıt denemesi yapıldı. Lütfen birkaç dakika sonra tekrar deneyin.',
                ]);
        }

        $password        = (string) $this->request->getPost('password');
        $passwordConfirm = (string) $this->request->getPost('password_confirm');

        if (! PasswordPolicy::accepts($password)) {
            return redirect()->back()->withInput()->with('errors', [
                'password' => PasswordPolicy::minimumLengthMessage(),
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
        $verificationCode = $this->generateVerificationCode();
        $now = time();
        $userModel = new UserModel();

        $data = [
            'username'      => trim((string) $this->request->getPost('username')),
            'email'         => strtolower(trim((string) $this->request->getPost('email'))),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),

            'role'          => 'user',
            'theme'         => 'system',

            'email_verified_at' => null,

            'email_verification_token' =>
                password_hash($verificationCode, PASSWORD_DEFAULT),

            'email_verification_expires_at' =>
                date(
                    'Y-m-d H:i:s',
                    $now + self::EMAIL_VERIFICATION_TTL_SECONDS
                ),

            'email_verification_attempts' => 0,

            'email_verification_sent_at' =>
                date('Y-m-d H:i:s', $now),

        ];

        if (! $userModel->insert($data)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('errors', $userModel->errors());
        }

        $userId = (int) $userModel->getInsertID();
        session()->set('verification_email', $data['email']);

        if (! $this->sendVerificationCodeEmail(
            $data,
            $verificationCode,
            false
        )) {
            $failedEmailUpdates = [
                'email_verification_token' => null,
                'email_verification_expires_at' => null,
                'email_verification_attempts' => 0,
                'email_verification_sent_at' => null,
            ];

            $userModel
                ->skipValidation(true)
                ->update($userId, $failedEmailUpdates);

            $data = array_merge(
                $data,
                $failedEmailUpdates
            );

            log_message(
                'error',
                'Kullanıcı #{id} için doğrulama kodu e-postası gönderilemedi.',
                ['id' => $userId]
            );

            AuditLogger::record(
                $userId,
                'auth.register_email_failed',
                'Kullanıcı oluşturuldu ancak doğrulama kodu e-postası gönderilemedi',
                'POST',
                'register',
                500
            );

            return redirect()
                ->to(site_url('verify-email'))
                ->with(
                    'error',
                    'Hesabınız oluşturuldu fakat doğrulama kodu e-postası gönderilemedi.'
                )
                ->with(
                    'verification_pending',
                    $this->verificationPendingData($data)
                );
        }

        AuditLogger::record(
            $userId,
            'auth.register',
            'Yeni kullanıcı hesabı oluşturuldu ve doğrulama kodu gönderildi',
            'POST',
            'register',
            201
        );

        return redirect()
            ->to(site_url('verify-email'))
            ->with(
                'success',
                'Hesabınız oluşturuldu. E-posta adresinize gönderilen 6 haneli doğrulama kodunu girin.'
            )
            ->with(
                'verification_pending',
                $this->verificationPendingData($data)
            );
    }

    public function login()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(site_url('dashboard'));
        }

        return view('auth/login');
    }

    public function verificationPage()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(site_url('dashboard'));
        }

        $email = strtolower(trim((string) session()->get('verification_email')));
        if ($email === '') {
            return redirect()->to(site_url('login'))->with('error', 'Önce doğrulama kodu isteyin.');
        }

        $user = (new UserModel())->where('email', $email)->first();
        if (! $user) {
            session()->remove('verification_email');
            return redirect()->to(site_url('login'))->with('error', 'Doğrulama isteği bulunamadı.');
        }
        if (! empty($user['email_verified_at'])) {
            session()->remove('verification_email');
            return redirect()->to(site_url('login'))->with('success', 'E-posta adresiniz zaten doğrulanmış.');
        }

        return view('auth/verify_email', [
            'verification' => $this->verificationPendingData($user),
        ]);
    }

    public function storeLogin()
    {

            if (session()->get('logged_in')) {
                return redirect()->to(site_url('dashboard'));
            }

            $email = strtolower(
                trim((string) $this->request->getPost('email'))
            );

            if ($this->rateLimited(
                'login:' . hash('sha256', $email),
                10
            )) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('errors', [
                        'login' => 'Çok fazla giriş denemesi yapıldı. Lütfen birkaç dakika sonra tekrar deneyin.',
                    ]);
            }

            $password = (string) $this->request->getPost('password');

            $user = (new UserModel())
                ->where('email', $email)
                ->first();

            if (
                ! $user
                || ! password_verify(
                    $password,
                    $user['password_hash']
                )
            ) {
                AuditLogger::record(
                    $user ? (int) $user['id'] : null,
                    'auth.login_failed',
                    'Başarısız giriş denemesi',
                    'POST',
                    'login',
                    401
                );

                return redirect()
                    ->back()
                    ->withInput()
                    ->with('errors', [
                        'login' => 'E-posta veya şifre hatalı.',
                    ]);
            }

            if ((int) ($user['is_active'] ?? 1) !== 1) {
                AuditLogger::record(
                    (int) $user['id'],
                    'auth.login_blocked',
                    'Pasif hesaba giriş denemesi',
                    'POST',
                    'login',
                    403
                );

                return redirect()
                    ->back()
                    ->withInput()
                    ->with('errors', [
                        'login' => 'Hesabınız devre dışı bırakılmış.',
                    ]);
            }

            if (empty($user['email_verified_at'])) {
                session()->set(
                    'verification_email',
                    $user['email']
                );

                return redirect()
                    ->to(site_url('verify-email'))
                    ->with(
                        'verification_pending',
                        $this->verificationPendingData($user)
                    );
            }

            session()->regenerate(true);

            $this->clearRateLimit(
                'login:' . hash('sha256', $email)
            );

            session()->set([
                'user_id' => (int) $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'user',
                'theme' => $user['theme'] ?? 'system',
                'notifications_enabled' => (int) (
                    $user['notifications_enabled'] ?? 0
                ),
                'experience_points' => (int) (
                    $user['experience_points'] ?? 0
                ),
                'auth_version' => (int) ($user['auth_version'] ?? 1),
                'logged_in' => true,
            ]);

            (new UserModel())
                ->skipValidation(true)
                ->update(
                    (int) $user['id'],
                    [
                        'last_seen_at' => date('Y-m-d H:i:s'),
                    ]
                );

            AuditLogger::record(
                (int) $user['id'],
                'auth.login',
                'Kullanıcı giriş yaptı',
                'POST',
                'login',
                200
            );

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

    public function verifyEmail()
{
    if (session()->get('logged_in')) {
        return redirect()->to(site_url('dashboard'));
    }

    $emailAddress = strtolower(trim((string) ($this->request->getPost('email') ?: session()->get('verification_email'))));

    $code = trim(
        (string) $this->request->getPost('code')
    );

    $userModel = new UserModel();

    $user = $emailAddress !== ''
        ? $userModel
            ->where('email', $emailAddress)
            ->first()
        : null;

    if (! $user) {
        session()->remove('verification_email');
        return redirect()
            ->to(site_url('login'))
            ->with(
                'error',
                'Doğrulama isteği geçersiz.'
            );
    }

    if (! empty($user['email_verified_at'])) {
        session()->remove('verification_email');
        return redirect()
            ->to(site_url('login'))
            ->with(
                'success',
                'E-posta adresiniz zaten doğrulanmış. Giriş yapabilirsiniz.'
            );
    }

    $pending =
        $this->verificationPendingData($user);
    session()->set('verification_email', $user['email']);

    if (! preg_match('/^\d{6}$/', $code)) {
        return redirect()
            ->to(site_url('verify-email'))
            ->with(
                'error',
                'Doğrulama kodu 6 rakamdan oluşmalıdır.'
            )
            ->with(
                'verification_pending',
                $pending
            );
    }

    $storedHash =
        (string) (
            $user['email_verification_token'] ?? ''
        );

    $expiresAt =
        $user['email_verification_expires_at']
        ?? null;

    $attempts =
        (int) (
            $user['email_verification_attempts']
            ?? 0
        );

    /*
     * Eski link tabanlı token kayıtları
     * password_hash formatında değildir.
     */
    if (
        $storedHash === ''
        || password_get_info($storedHash)['algo'] === null
    ) {
        return redirect()
            ->to(site_url('verify-email'))
            ->with(
                'error',
                'Eski doğrulama bağlantınız artık kullanılamıyor. Yeni bir doğrulama kodu isteyin.'
            )
            ->with(
                'verification_pending',
                array_merge(
                    $pending,
                    [
                        'can_resend' => true,
                        'can_verify' => false,
                    ]
                )
            );
    }

    if (
        empty($expiresAt)
        || strtotime($expiresAt) <= time()
    ) {
        return redirect()
            ->to(site_url('verify-email'))
            ->with(
                'error',
                'Doğrulama kodunun süresi dolmuş. Yeni bir kod isteyin.'
            )
            ->with(
                'verification_pending',
                $pending
            );
    }

    if (
        $attempts
        >= self::EMAIL_VERIFICATION_MAX_ATTEMPTS
    ) {
        return redirect()
            ->to(site_url('verify-email'))
            ->with(
                'error',
                'Çok fazla hatalı kod denemesi yapıldı. Yeni bir doğrulama kodu isteyin.'
            )
            ->with(
                'verification_pending',
                $pending
            );
    }

    if (! password_verify($code, $storedHash)) {
        $attempts++;

        $updates = [
            'email_verification_attempts'
                => $attempts,
        ];

        if (
            $attempts
            >= self::EMAIL_VERIFICATION_MAX_ATTEMPTS
        ) {
            $updates['email_verification_token']
                = null;

            $updates['email_verification_expires_at']
                = null;
        }

        $userModel
            ->skipValidation(true)
            ->update(
                (int) $user['id'],
                $updates
            );

        $user = array_merge(
            $user,
            $updates
        );

        $remaining = max(
            0,
            self::EMAIL_VERIFICATION_MAX_ATTEMPTS
                - $attempts
        );

        AuditLogger::record(
            (int) $user['id'],
            'auth.email_verification_failed',
            'Hatalı e-posta doğrulama kodu girildi',
            'POST',
            'verify-email',
            422
        );

        $message = $remaining > 0
            ? 'Doğrulama kodu hatalı. Kalan deneme hakkı: '
                . $remaining . '.'
            : 'Çok fazla hatalı deneme yapıldı. Yeni bir doğrulama kodu isteyin.';

        return redirect()
            ->to(site_url('verify-email'))
            ->with(
                'error',
                $message
            )
            ->with(
                'verification_pending',
                $this->verificationPendingData($user)
            );
    }

    $updated = $userModel
        ->skipValidation(true)
        ->update(
            (int) $user['id'],
            [
                'email_verified_at'
                    => date('Y-m-d H:i:s'),

                'email_verification_token'
                    => null,

                'email_verification_expires_at'
                    => null,

                'email_verification_attempts'
                    => 0,

                'email_verification_sent_at'
                    => null,
            ]
        );

    if (! $updated) {
        return redirect()
            ->to(site_url('verify-email'))
            ->with(
                'error',
                'E-posta doğrulanırken bir hata oluştu.'
            )
            ->with(
                'verification_pending',
                $pending
            );
    }

    AuditLogger::record(
        (int) $user['id'],
        'auth.email_verified',
        'Kullanıcının e-posta adresi 6 haneli kod ile doğrulandı',
        'POST',
        'verify-email',
        200
    );

    session()->remove('verification_email');

    return redirect()
        ->to(site_url('login'))
        ->with(
            'success',
            'E-posta adresiniz başarıyla doğrulandı. Şimdi giriş yapabilirsiniz.'
        );
}


    public function resendVerification()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(
                site_url('dashboard')
            );
        }

        $emailAddress = strtolower(trim((string) ($this->request->getPost('email') ?: session()->get('verification_email'))));

        if ($this->rateLimited('verification:' . hash('sha256', $emailAddress), 5)) {
            return $this->genericVerificationResendResponse();
        }

        if ($emailAddress === '') {
            return $this->genericVerificationResendResponse();
        }

        $userModel = new UserModel();

        $user = $userModel
            ->where('email', $emailAddress)
            ->first();

        if (! $user) {
            return $this->genericVerificationResendResponse();
        }

        session()->set('verification_email', $user['email']);

        if (! empty($user['email_verified_at'])) {
            session()->remove('verification_email');
            return $this->genericVerificationResendResponse();
        }

        $sentAt =
            $user['email_verification_sent_at']
            ?? null;

        if (! empty($sentAt)) {
            $secondsSinceSend =
                time() - strtotime($sentAt);

            if (
                $secondsSinceSend
                < self::EMAIL_VERIFICATION_RESEND_COOLDOWN_SECONDS
            ) {
                return $this->genericVerificationResendResponse();
            }
        }

        $verificationCode =
            $this->generateVerificationCode();

        $now = time();

        $updates = [
            'email_verification_token' =>
                password_hash(
                    $verificationCode,
                    PASSWORD_DEFAULT
                ),

            'email_verification_expires_at' =>
                date(
                    'Y-m-d H:i:s',
                    $now
                    + self::EMAIL_VERIFICATION_TTL_SECONDS
                ),

            'email_verification_attempts' => 0,

            'email_verification_sent_at' =>
                date('Y-m-d H:i:s', $now),
        ];

        $updated = $userModel
            ->skipValidation(true)
            ->update(
                (int) $user['id'],
                $updates
            );

        if (! $updated) {
            return $this->genericVerificationResendResponse();
        }

        $user = array_merge(
            $user,
            $updates
        );

        if (
            ! $this->sendVerificationCodeEmail(
                $user,
                $verificationCode,
                true
            )
        ) {
            $failedEmailUpdates = [
                'email_verification_token' => null,
                'email_verification_expires_at' => null,
                'email_verification_attempts' => 0,
                'email_verification_sent_at' => null,
            ];

            $userModel
                ->skipValidation(true)
                ->update(
                    (int) $user['id'],
                    $failedEmailUpdates
                );

            $user = array_merge(
                $user,
                $failedEmailUpdates
            );

            return $this->genericVerificationResendResponse();
        }

        AuditLogger::record(
            (int) $user['id'],
            'auth.verification_resent',
            'Yeni 6 haneli e-posta doğrulama kodu gönderildi',
            'POST',
            'resend-verification',
            200
        );

        return $this->genericVerificationResendResponse();
    }

    public function sendPasswordReset()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(site_url('dashboard'));
        }

        $emailAddress = strtolower(
            trim((string) $this->request->getPost('email'))
        );

        if ($emailAddress === '') {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'E-posta adresi zorunludur.'
                );
        }

        if ($this->rateLimited('password-reset:' . hash('sha256', $emailAddress), 5)) {
            return redirect()->to(site_url('forgot-password'))->with('success', 'Bu e-posta adresi sistemde kayıtlıysa şifre sıfırlama bağlantısı gönderildi.');
        }

        $userModel = new UserModel();

        $user = $userModel
            ->where('email', $emailAddress)
            ->first();

        /*
        * Güvenlik için kullanıcı var/yok bilgisini
        * dışarıya açık etmiyoruz.
        */
        if (! $user) {
            return redirect()
                ->to(site_url('forgot-password'))
                ->with(
                    'success',
                    'Bu e-posta adresi sistemde kayıtlıysa şifre sıfırlama bağlantısı gönderildi.'
                );
        }

        /*
        * Hesap devre dışıysa reset maili göndermeyelim.
        */
        if ((int) ($user['is_active'] ?? 1) !== 1) {
            return redirect()
                ->to(site_url('forgot-password'))
                ->with(
                    'success',
                    'Bu e-posta adresi sistemde kayıtlıysa şifre sıfırlama bağlantısı gönderildi.'
                );
        }

        /*
        * Gerçek token.
        * Mailde bu token olacak.
        */
        $resetToken = bin2hex(
            random_bytes(32)
        );

        /*
        * Database'e gerçek token yerine
        * SHA-256 hash'ini kaydediyoruz.
        */
        $resetTokenHash = hash(
            'sha256',
            $resetToken
        );

        $expiresAt = date(
            'Y-m-d H:i:s',
            strtotime('+30 minutes')
        );

        $updated = $userModel
            ->skipValidation(true)
            ->update(
                $user['id'],
                [
                    'password_reset_token' => $resetTokenHash,
                    'password_reset_expires_at' => $expiresAt,
                ]
            );

        if (! $updated) {
            return redirect()
                ->to(site_url('forgot-password'))
                ->with(
                    'error',
                    'Şifre sıfırlama bağlantısı oluşturulamadı.'
                );
        }

        /*
        * Maildeki gerçek reset URL.
        */
        $resetUrl = site_url(
            'reset-password/' . $resetToken
        );

        $email = service('email');

        $email->setTo($user['email']);

        $email->setSubject(
            'Project Redemption - Şifre Sıfırlama'
        );

        $email->setMailType('html');

        $email->setMessage(
            '
            <h2>Project Redemption</h2>

            <p>
                Merhaba ' . esc($user['username']) . ',
            </p>

            <p>
                Hesabınız için bir şifre sıfırlama isteği aldık.
            </p>

            <p>
                Yeni bir şifre belirlemek için aşağıdaki bağlantıya tıklayın:
            </p>

            <p>
                <a href="' . esc($resetUrl) . '">
                    Şifremi Sıfırla
                </a>
            </p>

            <p>
                Bu bağlantı 30 dakika boyunca geçerlidir.
            </p>

            <p>
                Bu isteği siz yapmadıysanız bu e-postayı görmezden gelebilirsiniz.
                Mevcut şifreniz değişmeyecektir.
            </p>
            '
        );

        if (! $email->send(false)) {

            /*
            * Mail gönderilemediyse oluşturduğumuz
            * reset tokenı da temizleyelim.
            */
            $userModel
                ->skipValidation(true)
                ->update(
                    $user['id'],
                    [
                        'password_reset_token' => null,
                        'password_reset_expires_at' => null,
                    ]
                );

            log_message(
                'error',
                'Şifre sıfırlama e-postası gönderilemedi. User ID: {userId}',
                ['userId' => (int) $user['id']]
            );

            return redirect()
                ->to(site_url('forgot-password'))
                ->with(
                    'error',
                    'Şifre sıfırlama e-postası gönderilemedi.'
                );
        }

        AuditLogger::record(
            (int) $user['id'],
            'auth.password_reset_requested',
            'Kullanıcı şifre sıfırlama bağlantısı istedi',
            'POST',
            'forgot-password',
            200
        );

        return redirect()
            ->to(site_url('forgot-password'))
            ->with(
                'success',
                'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi. Lütfen spam klasörünüzü de kontrol edin.'
            );
    }

    public function resetPassword(string $token)
    {
        if (session()->get('logged_in')) {
            return redirect()->to(site_url('dashboard'));
        }

        if ($token === '') {
            return redirect()
                ->to(site_url('forgot-password'))
                ->with('error', 'Geçersiz şifre sıfırlama bağlantısı.');
        }

        $tokenHash = hash('sha256', $token);

        $userModel = new UserModel();

        $user = $userModel
            ->where('password_reset_token', $tokenHash)
            ->first();

        if (! $user) {
            return redirect()
                ->to(site_url('forgot-password'))
                ->with(
                    'error',
                    'Şifre sıfırlama bağlantısı geçersiz veya daha önce kullanılmış.'
                );
        }

        if (
            empty($user['password_reset_expires_at'])
            || strtotime($user['password_reset_expires_at']) < time()
        ) {
            return redirect()
                ->to(site_url('forgot-password'))
                ->with(
                    'error',
                    'Şifre sıfırlama bağlantısının süresi dolmuş.'
                );
        }

        return view('auth/reset_password', [
            'token' => $token,
        ]);
    }

    public function updatePassword(string $token)
    {
        if (session()->get('logged_in')) {
            return redirect()->to(site_url('dashboard'));
        }

        if ($token === '') {
            return redirect()
                ->to(site_url('forgot-password'))
                ->with('error', 'Geçersiz şifre sıfırlama bağlantısı.');
        }

        $password = (string) $this->request->getPost('password');
        $passwordConfirm = (string) $this->request->getPost('password_confirm');

        if (! PasswordPolicy::accepts($password)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('errors', [
                    'password' => PasswordPolicy::minimumLengthMessage(),
                ]);
        }

        if ($password !== $passwordConfirm) {
            return redirect()
                ->back()
                ->withInput()
                ->with('errors', [
                    'password_confirm' => 'Şifreler eşleşmiyor.',
                ]);
        }

        $tokenHash = hash('sha256', $token);

        $userModel = new UserModel();

        $user = $userModel
            ->where('password_reset_token', $tokenHash)
            ->first();

        if (! $user) {
            return redirect()
                ->to(site_url('forgot-password'))
                ->with(
                    'error',
                    'Şifre sıfırlama bağlantısı geçersiz veya daha önce kullanılmış.'
                );
        }

        if (
            empty($user['password_reset_expires_at'])
            || strtotime($user['password_reset_expires_at']) < time()
        ) {
            return redirect()
                ->to(site_url('forgot-password'))
                ->with(
                    'error',
                    'Şifre sıfırlama bağlantısının süresi dolmuş.'
                );
        }

        $updated = $userModel
            ->skipValidation(true)
            ->update(
                $user['id'],
                [
                    'password_hash' => password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    ),

                    'auth_version' => (int) ($user['auth_version'] ?? 1) + 1,

                    'password_reset_token' => null,
                    'password_reset_expires_at' => null,
                ]
            );

        if (! $updated) {
            return redirect()
                ->back()
                ->with('error', 'Şifre değiştirilemedi.');
        }

        cache()->delete('auth_user_' . $user['id']);

        AuditLogger::record(
            (int) $user['id'],
            'auth.password_reset_completed',
            'Kullanıcı şifresini sıfırladı',
            'POST',
            'reset-password',
            200
        );

        return redirect()
            ->to(site_url('login'))
            ->with(
                'success',
                'Şifreniz başarıyla değiştirildi. Yeni şifrenizle giriş yapabilirsiniz.'
            );
    }
    private function generateVerificationCode(): string
    {
        return str_pad(
            (string) random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    private function genericVerificationResendResponse()
    {
        return redirect()
            ->to(site_url('login'))
            ->with('success', 'Hesap uygunsa doğrulama kodu gönderildi.');
    }

    private function rateLimited(string $action, int $maximumAttempts): bool
    {
        return (new AuthRateLimiter(new DatabaseAuthRateLimitStore(db_connect()), self::AUTH_RATE_WINDOW_SECONDS))->hit(
            $action,
            (string) $this->request->getIPAddress(),
            $maximumAttempts
        );
    }

    private function clearRateLimit(string $action): void
    {
        (new AuthRateLimiter(new DatabaseAuthRateLimitStore(db_connect()), self::AUTH_RATE_WINDOW_SECONDS))->clear(
            $action,
            (string) $this->request->getIPAddress()
        );
    }

    private function verificationPendingData(
        array $user
    ): array {
        $now = time();

        $expiresAt =
            $user['email_verification_expires_at']
            ?? null;

        $sentAt =
            $user['email_verification_sent_at']
            ?? null;

        $attempts =
            (int) (
                $user['email_verification_attempts']
                ?? 0
            );

        $storedHash =
            (string) (
                $user['email_verification_token']
                ?? ''
            );

        $isNewCodeHash =
            $storedHash !== ''
            && password_get_info($storedHash)['algo']
                !== null;

        $expiresTimestamp =
            ! empty($expiresAt)
            ? strtotime($expiresAt)
            : false;

        $sentTimestamp =
            ! empty($sentAt)
            ? strtotime($sentAt)
            : false;

        $secondsSinceSend =
            $sentTimestamp !== false
            ? max(
                0,
                $now - $sentTimestamp
            )
            : self::EMAIL_VERIFICATION_RESEND_COOLDOWN_SECONDS;

        return [
            'email' =>
                (string) (
                    $user['email']
                    ?? ''
                ),

            'can_verify' =>
                $isNewCodeHash
                && $expiresTimestamp !== false
                && $expiresTimestamp > $now
                && $attempts
                    < self::EMAIL_VERIFICATION_MAX_ATTEMPTS,

            'can_resend' =>
                ! $isNewCodeHash
                || $secondsSinceSend
                    >= self::EMAIL_VERIFICATION_RESEND_COOLDOWN_SECONDS,

            'expires_at' =>
                $expiresAt,

            'attempts_remaining' =>
                max(
                    0,
                    self::EMAIL_VERIFICATION_MAX_ATTEMPTS
                    - $attempts
                ),

            'resend_after_seconds' =>
                max(
                    0,
                    self::EMAIL_VERIFICATION_RESEND_COOLDOWN_SECONDS
                    - $secondsSinceSend
                ),
        ];
    }

    private function sendVerificationCodeEmail(
        array $user,
        string $verificationCode,
        bool $isResend
    ): bool {
        $email = service('email');

        $email->setTo(
            (string) $user['email']
        );

        $email->setSubject(
            $isResend
                ? 'Project Redemption - Yeni Doğrulama Kodunuz'
                : 'Project Redemption - E-posta Doğrulama Kodunuz'
        );

        $email->setMailType('html');

        $username = esc(
            (string) (
                $user['username'] ?? ''
            )
        );

        $code = esc($verificationCode);

        $email->setMessage('
            <h2>Project Redemption</h2>

            <p>
                Merhaba ' . $username . ',
            </p>

            <p>
                E-posta adresinizi doğrulamak için
                aşağıdaki 6 haneli kodu giriş
                ekranına yazın:
            </p>

            <p
                style="
                    font-size:32px;
                    font-weight:700;
                    letter-spacing:8px;
                    margin:24px 0;
                "
            >
                ' . $code . '
            </p>

            <p>
                Bu kod 10 dakika boyunca geçerlidir.
            </p>

            <p>
                Bu işlemi siz yapmadıysanız
                bu e-postayı görmezden gelebilirsiniz.
            </p>
        ');

        if ($email->send(false)) {
            return true;
        }

        log_message(
            'error',
            'Doğrulama kodu e-postası gönderilemedi. User ID: {userId}; resend: {isResend}',
            [
                'userId'  => (int) ($user['id'] ?? 0),
                'isResend' => $isResend ? 'yes' : 'no',
            ]
        );

        return false;
        }
}
