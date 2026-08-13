<?php

namespace App\Controllers;

use App\Models\DirectConversationModel;
use App\Models\NotificationModel;
use App\Libraries\TaskReminderService;
use App\Libraries\GameRoomService;

class LiveUpdates extends BaseController
{
    public function status()
    {
        $userId = (int) session()->get('user_id');
        (new GameRoomService())->cleanupInactiveRooms();
        (new TaskReminderService())->createDueSoonNotifications($userId);
        $db = db_connect();

        $latestMessage = $db->table('direct_messages dm')
            ->select('dm.id, dm.body, dm.conversation_id, dm.created_at, users.username AS sender_username')
            ->join('direct_conversations dc', 'dc.id = dm.conversation_id')
            ->join('users', 'users.id = dm.sender_id')
            ->groupStart()->where('dc.user_one_id', $userId)->orWhere('dc.user_two_id', $userId)->groupEnd()
            ->where('dm.sender_id !=', $userId)->where('dm.read_at', null)
            ->where('dm.deleted_by_recipient', 0)->orderBy('dm.id', 'DESC')->limit(1)->get()->getRowArray();

        $latestNotification = $db->table('notifications n')
            ->select('n.id, n.message, n.note_id, n.target_path, n.created_at')
            ->where('n.user_id', $userId)->where('n.read_at', null)
            ->orderBy('n.id', 'DESC')->limit(1)->get()->getRowArray();

        $latestPublicNote = $db->table('notes')
            ->select('notes.id, notes.title, notes.created_at, users.username AS owner_name')
            ->join('users', 'users.id = notes.user_id')
            ->where('notes.is_public', 1)->where('notes.user_id !=', $userId)
            ->orderBy('notes.id', 'DESC')->limit(1)->get()->getRowArray();

        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON([
            'success' => true,
            'messageUnread' => (new DirectConversationModel())->unreadCount($userId),
            'notificationUnread' => (new NotificationModel())->unreadCount($userId),
            'latestMessage' => $latestMessage ? [
                'id' => (int) $latestMessage['id'],
                'title' => $latestMessage['sender_username'],
                'text' => mb_strimwidth($latestMessage['body'], 0, 90, '…'),
                'url' => site_url('messages/' . $latestMessage['conversation_id']),
            ] : null,
            'latestNotification' => $latestNotification ? [
                'id' => (int) $latestNotification['id'],
                'noteId' => $latestNotification['note_id'] ? (int) $latestNotification['note_id'] : null,
                'title' => 'Yeni bildirim',
                'text' => $latestNotification['message'],
                'url' => site_url('notifications/' . $latestNotification['id'] . '/open'),
            ] : null,
            'latestPublicNote' => $latestPublicNote ? [
                'id' => (int) $latestPublicNote['id'],
                'title' => 'Yeni public not',
                'text' => $latestPublicNote['owner_name'] . ': ' . mb_strimwidth($latestPublicNote['title'], 0, 80, '…'),
                'url' => site_url('notes/' . $latestPublicNote['id']),
            ] : null,
        ]);
    }
}
