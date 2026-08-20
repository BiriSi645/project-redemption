<?php

namespace App\Controllers;

use App\Libraries\GameRoomService;
use App\Libraries\FourPlayerGameService;
use App\Libraries\TaskReminderService;
use App\Libraries\NotificationActionPolicy;
use App\Models\GameRoomModel;
use App\Models\NotificationModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use RuntimeException;

class Notifications extends BaseController
{
    public function index()
    {
        (new TaskReminderService())->createDueSoonNotifications((int) session()->get('user_id'));
        $model = new NotificationModel();
        return view('notifications/index', [
            'title' => 'Bildirimler',
            'notifications' => $model->inbox((int) session()->get('user_id')),
            'pager' => $model->pager,
        ]);
    }

    public function preview()
    {
        $userId = (int) session()->get('user_id');
        (new TaskReminderService())->createDueSoonNotifications($userId);
        $model = new NotificationModel();
        $items = array_map(function (array $notification): array {
            return [
                'id' => (int) $notification['id'],
                'icon' => NotificationModel::icon($notification['type']),
                'message' => $notification['message'],
                'time' => $this->relativeTime($notification['created_at']),
                'unread' => empty($notification['read_at']),
                'url' => site_url('notifications/' . $notification['id'] . '/open'),
            ];
        }, $model->recentForUser($userId));

        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON([
            'success' => true,
            'unreadCount' => $model->unreadCount($userId),
            'notifications' => $items,
        ]);
    }

    public function open(int $id)
    {
        $notification = (new NotificationModel())
            ->where('user_id', (int) session()->get('user_id'))
            ->find($id);
        if (! $notification) {
            throw PageNotFoundException::forPageNotFound('Bildirim bulunamadı.');
        }

        // GET yalnızca güvenli yönlendirme yapar; okuma ve davet kabulü POST'tadır.
        return redirect()->to(site_url(NotificationActionPolicy::safeGetTarget($notification)));
    }

    public function read(int $id)
    {
        return $this->openNotification($id);
    }

    private function openNotification(int $id)
    {
        $model = new NotificationModel();
        $notification = $model->where('user_id', (int) session()->get('user_id'))->find($id);
        if (! $notification) {
            throw PageNotFoundException::forPageNotFound('Bildirim bulunamadı.');
        }

        $model->update($id, ['read_at' => date('Y-m-d H:i:s')]);
        if ($notification['type'] === 'game_invite' && ! empty($notification['game_room_id'])) {
            $room = (new GameRoomModel())->find($notification['game_room_id']);
            if (! $room) {
                return redirect()->to(site_url('notifications'))->with('error', 'Oyun odası artık mevcut değil.');
            }
            try {
                if (in_array($room['game'], ['okey101', 'monopoly'], true)) {
                    (new FourPlayerGameService())->join((int) session()->get('user_id'), $room['code']);
                } else {
                    (new GameRoomService())->join((int) session()->get('user_id'), $room['code']);
                }
                return redirect()->to(site_url('games/room/' . $room['code']));
            } catch (RuntimeException $e) {
                return redirect()->to(site_url('notifications'))->with('error', $e->getMessage());
            }
        }

        if (! empty($notification['target_path']) && preg_match('#^[a-zA-Z0-9/_-]+$#', $notification['target_path'])) {
            return redirect()->to(site_url($notification['target_path']));
        }
        return ! empty($notification['note_id']) ? redirect()->to(site_url('notes/' . $notification['note_id'])) : redirect()->to(site_url('notifications'));
    }

    public function readAll()
    {
        (new NotificationModel())->where('user_id', (int) session()->get('user_id'))
            ->where('read_at', null)->set(['read_at' => date('Y-m-d H:i:s')])->update();

        return redirect()->back()->with('success', 'Tüm bildirimler okundu olarak işaretlendi.');
    }

    private function relativeTime(?string $date): string
    {
        $seconds = max(0, time() - strtotime((string) $date));
        if ($seconds < 60) return 'şimdi';
        if ($seconds < 3600) return (int) floor($seconds / 60) . ' dk';
        if ($seconds < 86400) return (int) floor($seconds / 3600) . ' sa';
        return (int) floor($seconds / 86400) . ' gün';
    }
}
