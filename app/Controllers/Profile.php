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

class Profile extends BaseController
{
    public function index(): string
    {
        return view('profile/index', [
            'title' => 'Profil ve Ayarlar',
            'user'  => (new UserModel())->find((int) session()->get('user_id')),
        ]);
    }

    public function update()
    {
        $userId = (int) session()->get('user_id');
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $theme = (string) $this->request->getPost('theme');

        $rules = [
            'email' => "required|valid_email|is_unique[users.email,id,{$userId}]",
            'theme' => 'required|in_list[light,dark,system]',
        ];

        if (! $this->validateData(['email'=>$email,'theme'=>$theme], $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $notifications = $this->request->getPost('notifications_enabled') === '1' ? 1 : 0;
        (new UserModel())->skipValidation(true)->update($userId, [
            'email' => $email,
            'theme' => $theme,
            'language' => 'tr',
            'notifications_enabled' => $notifications,
        ]);

        session()->set(['email'=>$email,'theme'=>$theme,'notifications_enabled'=>$notifications]);

        return redirect()->to(site_url('profile'))->with('success', 'Profil ayarları güncellendi.');
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

        if (strlen($password) < 8 || $password !== (string) $this->request->getPost('password_confirm')) {
            return redirect()->back()->with('errors', ['password'=>'Yeni şifre en az 8 karakter olmalı ve tekrarıyla eşleşmelidir.']);
        }

        (new UserModel())->skipValidation(true)->update($userId, ['password_hash'=>password_hash($password, PASSWORD_DEFAULT)]);

        return redirect()->to(site_url('profile'))->with('success', 'Şifreniz değiştirildi.');
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

        (new UserModel())->delete($userId);
        session()->destroy();

        return redirect()->to(site_url('login'))->with('success', 'Hesabınız ve kişisel verileriniz silindi.');
    }
}
