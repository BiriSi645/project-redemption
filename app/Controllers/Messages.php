<?php

namespace App\Controllers;

use App\Models\DirectConversationModel;
use App\Models\DirectMessageModel;
use App\Models\UserBlockModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Messages extends BaseController
{
    public function preview()
    {
        $userId = (int) session()->get('user_id');
        session_write_close();

        $model = new DirectConversationModel();
        $conversations = $model->recentForUser($userId);

        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON([
            'success' => true,
            'unreadCount' => $model->unreadCount($userId),
            'conversations' => array_map(static fn(array $conversation): array => [
                'id' => (int) $conversation['id'],
                'username' => $conversation['other_username'],
                'initial' => mb_strtoupper(mb_substr($conversation['other_username'], 0, 1)),
                'preview' => mb_strimwidth($conversation['last_body'] ?? 'Henüz mesaj gönderilmedi.', 0, 72, '…'),
                'unreadCount' => (int) $conversation['unread_count'],
                'time' => $conversation['last_message_at'] ? date('d.m · H:i', strtotime($conversation['last_message_at'])) : 'Yeni',
                'url' => site_url('messages/' . $conversation['id']),
            ], $conversations),
        ]);
    }

    public function index()
    {
        $userId = (int) session()->get('user_id');
        $model = new DirectConversationModel();
        $blockedUsers = (new UserBlockModel())->select('user_blocks.*, users.username')
            ->join('users', 'users.id = user_blocks.blocked_id')
            ->where('blocker_id', $userId)->orderBy('users.username')->findAll();

        return view('messages/index', [
            'title' => 'Mesajlar',
            'conversations' => $model->forUser($userId),
            'pager' => $model->pager,
            'blockedUsers' => $blockedUsers,
        ]);
    }

    public function readAll()
    {
        $userId = (int) session()->get('user_id');
        $conversations = (new DirectConversationModel())->select('id')
            ->groupStart()->where('user_one_id', $userId)->orWhere('user_two_id', $userId)->groupEnd()
            ->findAll();
        $conversationIds = array_map('intval', array_column($conversations, 'id'));
        if ($conversationIds !== []) {
            (new DirectMessageModel())->whereIn('conversation_id', $conversationIds)
                ->groupStart()->where('sender_id !=', $userId)->orWhere('sender_id', null)->groupEnd()
                ->where('read_at', null)
                ->set(['read_at' => date('Y-m-d H:i:s')])->update();
        }

        return redirect()->back()->with('success', 'Tüm mesajlar okundu olarak işaretlendi.');
    }

    public function start(int $recipientId)
    {
        $userId = (int) session()->get('user_id');
        $recipient = (new UserModel())->select('id,is_active')->find($recipientId);
        if (! $recipient || (int) $recipient['is_active'] !== 1 || $recipientId === $userId) {
            throw PageNotFoundException::forPageNotFound('Kullanıcı bulunamadı.');
        }
        if ((new UserBlockModel())->existsBetween($userId, $recipientId)) {
            return redirect()->to(site_url('users/' . $recipientId))->with('errors', ['message'=>'Engelleme nedeniyle bu kullanıcıyla mesajlaşamazsınız.']);
        }

        [$one, $two] = $this->orderedUsers($userId, $recipientId);
        $model = new DirectConversationModel();
        $conversation = $model->where(['user_one_id'=>$one,'user_two_id'=>$two])->first();
        if (! $conversation) {
            try {
                $id = $model->insert(['user_one_id'=>$one,'user_two_id'=>$two], true);
            } catch (\Throwable) {
                $id = null;
            }
            if (! $id) {
                $conversation = (new DirectConversationModel())->where(['user_one_id'=>$one,'user_two_id'=>$two])->first();
                $id = $conversation['id'] ?? null;
            }
        } else {
            $id = $conversation['id'];
        }

        return redirect()->to(site_url('messages/' . $id));
    }

    public function show(int $id)
    {
        $userId = (int) session()->get('user_id');
        [$conversation, $other] = $this->conversationForUser($id, $userId);
        $this->markRead($id, $userId);
        $messages = $this->visibleMessages($id, $userId)->orderBy('id', 'DESC')->limit(101)->get()->getResultArray();
        $hasOlder = count($messages) > 100;
        if ($hasOlder) array_pop($messages);
        $messages = array_reverse($messages);
        $blockedByMe = $other['id'] !== null && (new UserBlockModel())->where(['blocker_id'=>$userId,'blocked_id'=>$other['id']])->first() !== null;
        $blockedByOther = $other['id'] !== null && (new UserBlockModel())->where(['blocker_id'=>$other['id'],'blocked_id'=>$userId])->first() !== null;

        return view('messages/show', [
            'title' => $other['username'] . ' ile mesajlar',
            'conversation' => $conversation,
            'otherUser' => $other,
            'messages' => $messages,
            'userId' => $userId,
            'blockedByMe' => $blockedByMe,
            'blockedByOther' => $blockedByOther,
            'hasOlder' => $hasOlder,
        ]);
    }

    public function send(int $id)
    {
        $userId = (int) session()->get('user_id');
        [, $other] = $this->conversationForUser($id, $userId);
        if ((int) $other['is_active'] !== 1) {
            return $this->messageError('Devre dışı hesaba mesaj gönderilemez.', 403);
        }
        if ((new UserBlockModel())->existsBetween($userId, (int) $other['id'])) {
            return $this->messageError('Engelleme nedeniyle mesaj gönderilemez.', 403);
        }

        $model = new DirectMessageModel();
        $messageId = $model->insert([
            'conversation_id'=>$id,
            'sender_id'=>$userId,
            'body'=>trim((string) $this->request->getPost('body')),
            'created_at'=>date('Y-m-d H:i:s'),
        ], true);
        if (! $messageId) {
            return $this->messageError(implode(' ', $model->errors()), 422);
        }
        (new DirectConversationModel())->update($id, ['last_message_at'=>date('Y-m-d H:i:s')]);
        $message = $model->find($messageId);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success'=>true,'message'=>$this->messagePayload($message, $userId),'csrfHash'=>csrf_hash()]);
        }
        return redirect()->to(site_url('messages/' . $id));
    }

    public function poll(int $id)
    {
        $userId = (int) session()->get('user_id');
        $this->conversationForUser($id, $userId);
        $after = max(0, (int) $this->request->getGet('after'));
        $this->markRead($id, $userId);
        $rows = $this->visibleMessages($id, $userId)->where('id >', $after)->orderBy('id', 'ASC')->limit(100)->get()->getResultArray();
        $lastReadOwnId = (new DirectMessageModel())->selectMax('id')->where('conversation_id', $id)
            ->where('sender_id', $userId)->where('read_at IS NOT NULL', null, false)->first();

        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON([
            'success'=>true,
            'messages'=>array_map(fn(array $message) => $this->messagePayload($message, $userId), $rows),
            'lastReadOwnId'=>(int) ($lastReadOwnId['id'] ?? 0),
            'totalUnread'=>(new DirectConversationModel())->unreadCount($userId),
        ]);
    }

    public function history(int $id)
    {
        $userId = (int) session()->get('user_id');
        $this->conversationForUser($id, $userId);
        $before = max(1, (int) $this->request->getGet('before'));
        $rows = $this->visibleMessages($id, $userId)->where('id <', $before)->orderBy('id', 'DESC')->limit(101)->get()->getResultArray();
        $hasOlder = count($rows) > 100;
        if ($hasOlder) array_pop($rows);
        $rows = array_reverse($rows);

        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON([
            'success'=>true,
            'messages'=>array_map(fn(array $message) => $this->messagePayload($message, $userId), $rows),
            'hasOlder'=>$hasOlder,
        ]);
    }

    public function delete(int $conversationId, int $messageId)
    {
        $userId = (int) session()->get('user_id');
        $this->conversationForUser($conversationId, $userId);
        $model = new DirectMessageModel();
        $message = $model->where('conversation_id', $conversationId)->find($messageId);
        if (! $message) throw PageNotFoundException::forPageNotFound('Mesaj bulunamadı.');

        $field = (int) $message['sender_id'] === $userId ? 'deleted_by_sender' : 'deleted_by_recipient';
        $model->update($messageId, [$field=>1]);
        $fresh = $model->find($messageId);
        if ((int) $fresh['deleted_by_sender'] === 1 && (int) $fresh['deleted_by_recipient'] === 1) $model->delete($messageId);

        return redirect()->to(site_url('messages/' . $conversationId))->with('success', 'Mesaj sizin görünümünüzden silindi.');
    }

    public function block(int $userId)
    {
        $currentId = (int) session()->get('user_id');
        if ($userId === $currentId || ! (new UserModel())->find($userId)) throw PageNotFoundException::forPageNotFound('Kullanıcı bulunamadı.');
        $model = new UserBlockModel();
        if (! $model->where(['blocker_id'=>$currentId,'blocked_id'=>$userId])->first()) {
            $model->insert(['blocker_id'=>$currentId,'blocked_id'=>$userId,'created_at'=>date('Y-m-d H:i:s')]);
        }
        return redirect()->to(site_url('messages'))->with('success', 'Kullanıcı engellendi. Mevcut mesaj geçmişi korunur.');
    }

    public function unblock(int $userId)
    {
        (new UserBlockModel())->where(['blocker_id'=>(int) session()->get('user_id'),'blocked_id'=>$userId])->delete();
        return redirect()->back()->with('success', 'Kullanıcının engeli kaldırıldı.');
    }

    private function conversationForUser(int $id, int $userId): array
    {
        $conversation = (new DirectConversationModel())->find($id);
        if (! $conversation || ((int) $conversation['user_one_id'] !== $userId && (int) $conversation['user_two_id'] !== $userId)) {
            throw PageNotFoundException::forPageNotFound('Konuşma bulunamadı.');
        }
        $rawOtherId = (int) $conversation['user_one_id'] === $userId
            ? $conversation['user_two_id']
            : $conversation['user_one_id'];
        if ($rawOtherId === null) {
            return [$conversation, ['id'=>null, 'username'=>'Silinmiş kullanıcı', 'is_active'=>0, 'deleted'=>true]];
        }
        $otherId = (int) $rawOtherId;
        $other = (new UserModel())->select('id,username,is_active')->find($otherId);
        if (! $other) throw PageNotFoundException::forPageNotFound('Kullanıcı bulunamadı.');
        return [$conversation, $other];
    }

    private function visibleMessages(int $conversationId, int $userId)
    {
        return db_connect()->table('direct_messages')->where('conversation_id', $conversationId)
            ->groupStart()
                ->groupStart()->where('sender_id', $userId)->where('deleted_by_sender', 0)->groupEnd()
                ->orGroupStart()
                    ->groupStart()->where('sender_id !=', $userId)->orWhere('sender_id', null)->groupEnd()
                    ->where('deleted_by_recipient', 0)
                ->groupEnd()
            ->groupEnd();
    }

    private function markRead(int $conversationId, int $userId): void
    {
        (new DirectMessageModel())->where('conversation_id', $conversationId)
            ->groupStart()->where('sender_id !=', $userId)->orWhere('sender_id', null)->groupEnd()
            ->where('read_at', null)->set(['read_at'=>date('Y-m-d H:i:s')])->update();
    }

    private function messagePayload(array $message, int $userId): array
    {
        return ['id'=>(int)$message['id'],'body'=>$message['body'],'mine'=>(int)$message['sender_id']===$userId,
            'read'=>!empty($message['read_at']),'createdAt'=>$message['created_at'],
            'time'=>date('d.m.Y · H:i', strtotime($message['created_at']))];
    }

    private function messageError(string $message, int $status)
    {
        if ($this->request->isAJAX()) return $this->response->setStatusCode($status)->setJSON(['success'=>false,'message'=>$message,'csrfHash'=>csrf_hash()]);
        return redirect()->back()->withInput()->with('errors', ['message'=>$message]);
    }

    private function orderedUsers(int $first, int $second): array
    {
        return $first < $second ? [$first,$second] : [$second,$first];
    }
}
