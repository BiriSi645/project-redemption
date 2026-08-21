<?php

namespace App\Controllers;

use App\Models\JournalEntryModel;
use App\Models\NoteModel;
use App\Models\TaskModel;
use App\Models\UserModel;
use App\Models\HabitModel;
use App\Models\HabitCompletionModel;
use App\Models\NoteCommentModel;
use App\Models\GameScoreModel;
use App\Libraries\PasswordPolicy;

class Profile extends BaseController
{
    public function index(): string
    {
        return view('profile/index', [
            'title' => 'Profil ve Ayarlar',
            'user'  => (new UserModel())->find((int) session()->get('user_id')),
        ]);
    }

    public function password()
    {
        $userId = (int) session()->get('user_id');
        $user = (new UserModel())->find($userId);
        $current = (string) $this->request->getPost('current_password');
        $password = (string) $this->request->getPost('password');

        if (! $user || ! password_verify($current, $user['password_hash'])) {
            return redirect()->back()->with('errors', ['current_password'=>'Mevcut şifre hatalı.']);
        }

        if (! PasswordPolicy::accepts($password) || $password !== (string) $this->request->getPost('password_confirm')) {
            return redirect()->back()->with('errors', ['password'=>'Yeni şifre en az '.PasswordPolicy::MIN_LENGTH.' karakter olmalı ve tekrarıyla eşleşmelidir.']);
        }

        (new UserModel())->skipValidation(true)->update($userId, [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
        ]);

        return redirect()->to(site_url('profile'))->with('success', 'Şifreniz değiştirildi.');
    }

    public function requestEmailChange()
    {
        $userId = (int) session()->get('user_id');

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (! $user) {
            return redirect()
                ->to(site_url('profile'))
                ->with('errors', [
                    'email' => 'Kullanıcı bulunamadı.',
                ]);
        }

        $newEmail = strtolower(
            trim(
                (string) $this->request->getPost('new_email')
            )
        );

        $currentPassword =
            (string) $this->request->getPost('email_current_password');

        if ($currentPassword === '') {
            $currentPassword =
                (string) $this->request->getPost('current_password');
        }

        /*
        * 1. Mevcut şifre doğru mu?
        */
        if (
            $currentPassword === ''
            || ! password_verify(
                $currentPassword,
                $user['password_hash']
            )
        ) {
            return redirect()
                ->to(site_url('profile'))
                ->withInput()
                ->with('errors', [
                    'email' => 'Mevcut şifreniz hatalı.',
                ]);
        }

        /*
        * 2. Yeni e-posta geçerli mi?
        */
        if (
            $newEmail === ''
            || ! filter_var(
                $newEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            return redirect()
                ->to(site_url('profile'))
                ->withInput()
                ->with('errors', [
                    'email' => 'Geçerli bir e-posta adresi girin.',
                ]);
        }

        /*
        * 3. Zaten kullanılan e-posta ile
        * aynı adres olmasın.
        */
        if (
            strtolower((string) $user['email'])
            === $newEmail
        ) {
            return redirect()
                ->to(site_url('profile'))
                ->withInput()
                ->with('errors', [
                    'email' => 'Yeni e-posta adresiniz mevcut e-posta adresinizle aynı olamaz.',
                ]);
        }

        /*
        * 4. Başka kullanıcı bu adresi
        * kullanıyor mu?
        */
        $existingUser = $userModel
            ->where('email', $newEmail)
            ->first();

        if ($existingUser) {
            return redirect()
                ->to(site_url('profile'))
                ->withInput()
                ->with('errors', [
                    'email' => 'Bu e-posta adresi başka bir hesap tarafından kullanılıyor.',
                ]);
        }

        /*
        * Çok kısa sürede sürekli kod
        * gönderilmesini engelle.
        */
        if (
            ! empty($user['pending_email_verification_sent_at'])
        ) {
            $lastSent = strtotime(
                $user['pending_email_verification_sent_at']
            );

            if (
                $lastSent !== false
                && time() - $lastSent < 60
            ) {
                return redirect()
                    ->to(site_url('profile'))
                    ->with('errors', [
                        'email' => 'Yeni bir doğrulama kodu istemeden önce 60 saniye bekleyin.',
                    ]);
            }
        }

        /*
        * 5. 6 haneli doğrulama kodu üret.
        */
        $verificationCode = str_pad(
            (string) random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );

        /*
        * Kodu DB'de açık şekilde
        * saklamıyoruz.
        */
        $tokenHash = password_hash(
            $verificationCode,
            PASSWORD_DEFAULT
        );

        $now = date('Y-m-d H:i:s');

        $expiresAt = date(
            'Y-m-d H:i:s',
            time() + 600
        );

        /*
        * 6. Gerçek email alanına dokunma.
        * Yeni adresi pending_email olarak tut.
        */
        $updated = $userModel
            ->skipValidation(true)
            ->update($userId, [
                'pending_email' =>
                    $newEmail,

                'pending_email_verification_token' =>
                    $tokenHash,

                'pending_email_verification_expires_at' =>
                    $expiresAt,

                'pending_email_verification_attempts' =>
                    0,

                'pending_email_verification_sent_at' =>
                    $now,
            ]);

        if (! $updated) {
            return redirect()
                ->to(site_url('profile'))
                ->with('errors', [
                    'email' => 'E-posta değişikliği başlatılamadı.',
                ]);
        }

        /*
        * 7. Kodu YENİ e-posta adresine gönder.
        */
        if (
            ! $this->sendEmailChangeCode(
                $user,
                $newEmail,
                $verificationCode
            )
        ) {
            /*
            * Mail gönderilemediyse bekleyen
            * değişikliği temizle.
            */
            $userModel
                ->skipValidation(true)
                ->update($userId, [
                    'pending_email' => null,
                    'pending_email_verification_token' => null,
                    'pending_email_verification_expires_at' => null,
                    'pending_email_verification_attempts' => 0,
                    'pending_email_verification_sent_at' => null,
                ]);

            return redirect()
                ->to(site_url('profile'))
                ->with('errors', [
                    'email' => 'Doğrulama e-postası gönderilemedi. Daha sonra tekrar deneyin.',
                ]);
        }

        return redirect()
            ->to(site_url('profile'))
            ->with(
                'success',
                'Yeni e-posta adresinize 6 haneli doğrulama kodu gönderildi.'
            );
    }
    public function verifyEmailChange()
    {
        $userId = (int) session()->get('user_id');

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (! $user) {
            return redirect()
                ->to(site_url('profile'))
                ->with('errors', [
                    'email' => 'Kullanıcı bulunamadı.',
                ]);
        }

        $code = trim(
            (string) $this->request->getPost('code')
        );

        /*
        * Bekleyen bir e-posta değişikliği var mı?
        */
        if (
            empty($user['pending_email'])
            || empty($user['pending_email_verification_token'])
            || empty($user['pending_email_verification_expires_at'])
        ) {
            return redirect()
                ->to(site_url('profile'))
                ->with('errors', [
                    'email' => 'Bekleyen bir e-posta değişikliği bulunamadı.',
                ]);
        }

        /*
        * Kod mutlaka 6 rakam olmalı.
        */
        if (! preg_match('/^\d{6}$/', $code)) {
            return redirect()
                ->to(site_url('profile'))
                ->with('errors', [
                    'email' => '6 haneli doğrulama kodunu girin.',
                ]);
        }

        /*
        * Kodun süresi geçmiş mi?
        */
        $expiresAt = strtotime(
            $user['pending_email_verification_expires_at']
        );

        if (
            $expiresAt === false
            || $expiresAt < time()
        ) {
            $userModel
                ->skipValidation(true)
                ->update($userId, [
                    'pending_email' => null,
                    'pending_email_verification_token' => null,
                    'pending_email_verification_expires_at' => null,
                    'pending_email_verification_attempts' => 0,
                    'pending_email_verification_sent_at' => null,
                ]);

            return redirect()
                ->to(site_url('profile'))
                ->with('errors', [
                    'email' => 'Doğrulama kodunun süresi doldu. Yeni bir kod isteyin.',
                ]);
        }

        $attempts = (int) (
            $user['pending_email_verification_attempts'] ?? 0
        );

        /*
        * Çok fazla yanlış deneme yapılmış mı?
        */
        if ($attempts >= 5) {
            $userModel
                ->skipValidation(true)
                ->update($userId, [
                    'pending_email' => null,
                    'pending_email_verification_token' => null,
                    'pending_email_verification_expires_at' => null,
                    'pending_email_verification_attempts' => 0,
                    'pending_email_verification_sent_at' => null,
                ]);

            return redirect()
                ->to(site_url('profile'))
                ->with('errors', [
                    'email' => 'Çok fazla hatalı kod denemesi yapıldı. Yeni bir kod isteyin.',
                ]);
        }

        /*
        * Kod doğru mu?
        */
        if (! password_verify(
            $code,
            $user['pending_email_verification_token']
        )) {
            $attempts++;

            if ($attempts >= 5) {
                $userModel
                    ->skipValidation(true)
                    ->update($userId, [
                        'pending_email' => null,
                        'pending_email_verification_token' => null,
                        'pending_email_verification_expires_at' => null,
                        'pending_email_verification_attempts' => 0,
                        'pending_email_verification_sent_at' => null,
                    ]);

                return redirect()
                    ->to(site_url('profile'))
                    ->with('errors', [
                        'email' => 'Doğrulama kodu hatalı. Deneme sınırına ulaştınız; yeni bir kod isteyin.',
                    ]);
            }

            $userModel
                ->skipValidation(true)
                ->update($userId, [
                    'pending_email_verification_attempts' => $attempts,
                ]);

            return redirect()
                ->to(site_url('profile'))
                ->with('errors', [
                    'email' =>
                        'Doğrulama kodu hatalı. '
                        . (5 - $attempts)
                        . ' deneme hakkınız kaldı.',
                ]);
        }

        $newEmail = strtolower(
            trim((string) $user['pending_email'])
        );

        /*
        * Kod gönderildikten sonra başka bir hesap
        * bu adresi kullanmaya başlamış olabilir.
        * Son kez kontrol ediyoruz.
        */
        $existingUser = $userModel
            ->where('email', $newEmail)
            ->where('id !=', $userId)
            ->first();

        if ($existingUser) {
            $userModel
                ->skipValidation(true)
                ->update($userId, [
                    'pending_email' => null,
                    'pending_email_verification_token' => null,
                    'pending_email_verification_expires_at' => null,
                    'pending_email_verification_attempts' => 0,
                    'pending_email_verification_sent_at' => null,
                ]);

            return redirect()
                ->to(site_url('profile'))
                ->with('errors', [
                    'email' => 'Bu e-posta adresi artık başka bir hesap tarafından kullanılıyor.',
                ]);
        }

        /*
        * Artık yeni e-posta doğrulandı.
        * Gerçek email alanını değiştiriyoruz.
        */
        try {
            $updated = $userModel
                ->skipValidation(true)
                ->update($userId, [
                    'email' => $newEmail,

                    /*
                    * Yeni adres şu anda doğrulandığı için
                    * doğrulama zamanını yeniliyoruz.
                    */
                    'email_verified_at' => date('Y-m-d H:i:s'),

                    /*
                    * Eski password reset tokenı varsa
                    * güvenlik için geçersiz kıl.
                    */
                    'password_reset_token' => null,
                    'password_reset_expires_at' => null,

                    /*
                    * Bekleyen değişiklik tamamlandı.
                    */
                    'pending_email' => null,
                    'pending_email_verification_token' => null,
                    'pending_email_verification_expires_at' => null,
                    'pending_email_verification_attempts' => 0,
                    'pending_email_verification_sent_at' => null,
                ]);
        } catch (\Throwable $e) {
            log_message(
                'error',
                'E-posta değişikliği kaydedilemedi. User ID: '
                . $userId
                . ' Error: '
                . $e->getMessage()
            );

            return redirect()
                ->to(site_url('profile'))
                ->with('errors', [
                    'email' => 'E-posta adresi şu anda değiştirilemedi.',
                ]);
        }

        if (! $updated) {
            return redirect()
                ->to(site_url('profile'))
                ->with('errors', [
                    'email' => 'E-posta adresi değiştirilemedi.',
                ]);
        }

        /*
        * Session'daki e-posta da eski kalmasın.
        */
        session()->set(
            'email',
            $newEmail
        );

        return redirect()
            ->to(site_url('profile'))
            ->with(
                'success',
                'E-posta adresiniz başarıyla değiştirildi.'
            );
    }

    private function sendEmailChangeCode(
        array $user,
        string $newEmail,
        string $verificationCode
    ): bool {
        $email = service('email');

        $email->setTo($newEmail);

        $email->setSubject(
            'Project Redemption - E-posta Değişikliği'
        );

        $email->setMailType('html');

        $username = esc(
            (string) ($user['username'] ?? '')
        );

        $code = esc($verificationCode);

        $email->setMessage('
            <h2>Project Redemption</h2>

            <p>
                Merhaba ' . $username . ',
            </p>

            <p>
                Project Redemption hesabınızın
                e-posta adresini değiştirmek için
                aşağıdaki 6 haneli doğrulama kodunu
                kullanın:
            </p>

            <p
                style="
                    font-size: 32px;
                    font-weight: 700;
                    letter-spacing: 8px;
                    margin: 24px 0;
                "
            >
                ' . $code . '
            </p>

            <p>
                Bu kod 10 dakika boyunca geçerlidir.
            </p>

            <p>
                Bu işlemi siz başlatmadıysanız
                bu e-postayı görmezden gelebilirsiniz.
                Hesabınızın mevcut e-posta adresi
                değiştirilmemiştir.
            </p>
        ');

        if ($email->send(false)) {
            return true;
        }

        log_message(
            'error',
            'E-posta değişikliği doğrulama kodu gönderilemedi. User ID: {userId}',
            ['userId' => (int) ($user['id'] ?? 0)]
        );

        return false;
    }
    public function update()
    {
        $userId = (int) session()->get('user_id');
        $theme = (string) $this->request->getPost('theme');

        $rules = [
            'theme' => 'required|in_list[light,dark,system]',
        ];

        if (! $this->validateData(
            ['theme' => $theme],
            $rules
        )) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'errors',
                    $this->validator->getErrors()
                );
        }

        $notifications =
            $this->request->getPost('notifications_enabled') === '1'
                ? 1
                : 0;

        (new UserModel())
            ->skipValidation(true)
            ->update($userId, [
                'theme' => $theme,
                'language' => 'tr',
                'notifications_enabled' => $notifications,
            ]);

        session()->set([
            'theme' => $theme,
            'notifications_enabled' => $notifications,
        ]);

        return redirect()
            ->to(site_url('profile'))
            ->with(
                'success',
                'Profil ayarları güncellendi.'
            );
    }

    public function export(string $format)
    {
        if (! in_array($format, ['json','csv','txt'], true)) {
            return redirect()->to(site_url('profile'));
        }

        $userId = (int) session()->get('user_id');
        $data = [
            'exported_at' => date(DATE_ATOM),
            'user' => (new UserModel())->select('username,bio,email,created_at')->find($userId),
            'notes' => (new NoteModel())->where('user_id',$userId)->findAll(),
            'tasks' => (new TaskModel())->where('user_id',$userId)->findAll(),
            'journal_entries' => (new JournalEntryModel())->where('user_id',$userId)->findAll(),
            'habits' => (new HabitModel())->where('user_id',$userId)->findAll(),
            'habit_completions' => (new HabitCompletionModel())->where('user_id',$userId)->findAll(),
            'note_comments' => (new NoteCommentModel())->where('user_id',$userId)->findAll(),
            'game_scores' => (new GameScoreModel())->where('user_id',$userId)->findAll(),
        ];

        if ($format === 'json') {
            return $this->response->download('project-redemption-data.json', json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))->setContentType('application/json');
        }

        if ($format === 'csv') {
            $stream = fopen('php://temp', 'r+');
            fputcsv($stream, ['type','date','title','content','category/status']);
            $habitNames = array_column($data['habits'], 'title', 'id');
            foreach ($data['notes'] as $item) fputcsv($stream, ['note',$item['created_at'],$item['title'],$item['content'],$item['category']]);
            foreach ($data['tasks'] as $item) fputcsv($stream, ['task',$item['due_date'],$item['title'],$item['description'],$item['category'].'/'.$item['status']]);
            foreach ($data['journal_entries'] as $item) fputcsv($stream, ['journal',$item['entry_date'],$item['title'],$item['content'],$item['mood']]);
            foreach ($data['habits'] as $item) fputcsv($stream, ['habit',$item['created_at'],$item['title'],$item['description'],$item['frequency'].':'.$item['target_count'].'/'.((int)$item['is_active']===1?'active':'paused')]);
            foreach ($data['habit_completions'] as $item) fputcsv($stream, ['habit_completion',$item['completed_at'],$habitNames[$item['habit_id']] ?? 'Silinmiş alışkanlık','',$item['period_key']]);
            foreach ($data['note_comments'] as $item) fputcsv($stream, ['note_comment',$item['created_at'],'Not #'.$item['note_id'],$item['content'],'comment']);
            foreach ($data['game_scores'] as $item) fputcsv($stream, ['game_score',$item['updated_at'],$item['game'],$item['score'],$item['difficulty']]);
            rewind($stream);
            $content = stream_get_contents($stream);
            fclose($stream);
            return $this->response->download('project-redemption-data.csv', $content)->setContentType('text/csv');
        }

        $lines = ["PROJECT REDEMPTION VERİ YEDEĞİ", 'Oluşturulma: '.date('d.m.Y H:i'), ''];
        foreach (['notes'=>'NOTLAR','tasks'=>'GÖREVLER','journal_entries'=>'GÜNLÜK','habits'=>'ALIŞKANLIKLAR'] as $key=>$heading) {
            $lines[] = "=== {$heading} ===";
            foreach ($data[$key] as $item) $lines[] = ($item['entry_date'] ?? $item['due_date'] ?? $item['created_at'] ?? '').' | '.$item['title'].PHP_EOL.($item['content'] ?? $item['description'] ?? '').PHP_EOL;
        }
        $habitNames = array_column($data['habits'], 'title', 'id');
        $lines[] = '=== ALIŞKANLIK İŞARETLEMELERİ ===';
        foreach ($data['habit_completions'] as $item) {
            $lines[] = $item['completed_at'].' | '.($habitNames[$item['habit_id']] ?? 'Silinmiş alışkanlık').' | '.$item['period_key'];
        }
        $lines[] = '=== YAPTIĞIM YORUMLAR ===';
        foreach ($data['note_comments'] as $item) {
            $lines[] = $item['created_at'].' | Not #'.$item['note_id'].PHP_EOL.$item['content'].PHP_EOL;
        }
        $lines[] = '=== OYUN REKORLARI ===';
        foreach ($data['game_scores'] as $item) {
            $lines[] = $item['updated_at'].' | '.$item['game'].' | '.$item['difficulty'].' | '.$item['score'];
        }
        return $this->response->download('project-redemption-data.txt', implode(PHP_EOL,$lines))->setContentType('text/plain');
    }

    public function delete()
    {
        $userId = (int) session()->get('user_id');
        $user = (new UserModel())->find($userId);

        if (! $user || ! password_verify((string) $this->request->getPost('password'), $user['password_hash'])) {
            return redirect()->back()->with('errors', ['delete'=>'Hesabı silmek için doğru şifrenizi girin.']);
        }

        if ($user['role'] === 'admin' && (new UserModel())->where('role','admin')->where('is_active',1)->countAllResults() <= 1) {
            return redirect()->back()->with('errors', ['delete'=>'Sistemdeki son aktif admin hesabı silinemez.']);
        }

        $db = db_connect();
        $sharedOwnedProjects = $db
            ->table('projects')
            ->select('projects.id, projects.name')
            ->join('project_members', 'project_members.project_id = projects.id')
            ->where('projects.owner_id', $userId)
            ->where('project_members.user_id !=', $userId)
            ->where('project_members.status', 'accepted')
            ->groupBy(['projects.id', 'projects.name'])
            ->get()
            ->getResultArray();

        if ($sharedOwnedProjects !== []) {
            return redirect()->back()->with('errors', [
                'delete' => 'Başka üyeleri bulunan projelerin sahibisiniz. Ortak proje verilerinin silinmemesi için önce bu projelerin sahipliğini devretmelisiniz.',
            ]);
        }

        $db->transStart();

        $db->table('audit_logs')->where('user_id', $userId)->delete();
        $db->table('notifications')
            ->groupStart()
                ->where('user_id', $userId)
                ->orWhere('actor_user_id', $userId)
            ->groupEnd()
            ->delete();

        (new UserModel())->skipValidation(true)->delete($userId);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->with('errors', [
                'delete' => 'Hesabınız silinirken bir hata oluştu.',
            ]);
        }

        cache()->delete('auth_user_' . $userId);
        cache()->delete('admin_dashboard_summary_v1');
        session()->destroy();

        return redirect()->to(site_url('login'))->with('success', 'Hesabınız silindi. Diğer kullanıcıların konuşma geçmişindeki mesajlarınız korunur.');
    }
}
