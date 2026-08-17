<?php

namespace App\Controllers;

use App\Models\AnnouncementModel;
use App\Models\NotificationModel;
use App\Models\UserModel;
use RuntimeException;
use Throwable;

class AdminNotifications extends BaseController
{
    public function index()
    {
        return redirect()->to(site_url('admin/users') . '?section=notifications');
    }

    public function publish()
    {
        if (! $this->validate([
            'type' => 'required|in_list[update,announcement]',
            'title' => 'required|min_length[3]|max_length[150]',
            'content' => 'required|min_length[5]|max_length[10000]',
            'target_path' => 'permit_empty|max_length[255]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $targetPath = trim((string) $this->request->getPost('target_path'), " \t\n\r\0\x0B/");
        if ($targetPath !== '' && ! preg_match('#^[a-zA-Z0-9/_-]+$#', $targetPath)) {
            return redirect()->back()->withInput()->with('errors', [
                'target_path' => 'Bağlantı yalnızca site içi bir yol olmalı. Örnek: notes veya games/multiplayer',
            ]);
        }

        $type = (string) $this->request->getPost('type');
        $title = trim((string) $this->request->getPost('title'));
        $createdAt = date('Y-m-d H:i:s');
        $adminId = (int) session()->get('user_id');
        $db = db_connect();
        $db->transBegin();

        try {
            $announcementModel = new AnnouncementModel();
            $announcementId = $announcementModel->insert([
                'created_by' => $adminId,
                'type' => $type,
                'title' => $title,
                'content' => trim((string) $this->request->getPost('content')),
                'target_path' => $targetPath !== '' ? $targetPath : null,
                'recipient_count' => 0,
                'created_at' => $createdAt,
            ], true);
            if (! $announcementId) {
                throw new RuntimeException('Duyuru kaydı oluşturulamadı.');
            }

            $recipientCount = 0;
            $lastUserId = 0;
            do {
                $users = (new UserModel())->select('id')->where('id >', $lastUserId)->orderBy('id', 'ASC')->findAll(500);
                $notifications = [];
                foreach ($users as $user) {
                    $userId = (int) $user['id'];
                    $lastUserId = $userId;
                    $notifications[] = [
                        'user_id' => $userId,
                        'actor_user_id' => $adminId,
                        'type' => $type === 'update' ? 'system_update' : 'admin_announcement',
                        'message' => ($type === 'update' ? 'Güncelleme notu: ' : 'Yeni duyuru: ') . $title,
                        'target_path' => 'announcements/' . $announcementId,
                        'notification_key' => 'announcement:' . $announcementId . ':' . $userId,
                        'created_at' => $createdAt,
                    ];
                }
                if ($notifications !== []) {
                    (new NotificationModel())->insertBatch($notifications);
                    $recipientCount += count($notifications);
                }
            } while (count($users) === 500);

            $announcementModel->update($announcementId, ['recipient_count' => $recipientCount]);

            if (! $db->transStatus()) {
                throw new RuntimeException('Bildirimler kaydedilemedi.');
            }
            $db->transCommit();

            return redirect()->to(site_url('admin/users') . '?section=notifications')
                ->with('success', $recipientCount . ' kayıtlı kullanıcıya bildirim gönderildi.');
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Toplu bildirim yayınlanamadı: {message}', ['message' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Bildirim yayınlanamadı. Lütfen tekrar deneyin.');
        }
    }
}
