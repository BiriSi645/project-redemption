<?php

namespace App\Controllers;

use App\Libraries\GameRoomService;
use App\Libraries\FourPlayerGameService;
use App\Models\GameRoomModel;
use App\Models\NotificationModel;
use App\Models\UserModel;
use RuntimeException;

class GameRooms extends BaseController
{
    public function lobby(): string
    {
        return view('games/multiplayer', ['title' => 'Birlikte Oyna']);
    }

    public function create()
    {
        try {
            $game = (string) $this->request->getPost('game');
            $room = in_array($game, ['okey101', 'monopoly'], true)
                ? (new FourPlayerGameService())->create((int) session()->get('user_id'), $game, $this->request->getPost())
                : (new GameRoomService())->create((int) session()->get('user_id'), $game, (string) $this->request->getPost('difficulty'));
            return redirect()->to(site_url('games/room/' . $room['code']));
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function join()
    {
        try {
            $code = (string) $this->request->getPost('code');
            $fourPlayer = new FourPlayerGameService();
            $room = $fourPlayer->isFourPlayerGame($code)
                ? $fourPlayer->join((int) session()->get('user_id'), $code)
                : (new GameRoomService())->join((int) session()->get('user_id'), $code);
            return redirect()->to(site_url('games/room/' . $room['code']));
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(string $code)
    {
        try {
            $fourPlayer = new FourPlayerGameService();
            if ($fourPlayer->isFourPlayerGame($code)) {
                $room = $fourPlayer->getForPlayer($code, (int) session()->get('user_id'));
                $room['activeUsers'] = [];
                if ($room['status'] === 'waiting' && $room['isOwner'] && count($room['players']) < 4) {
                    $joinedIds = array_map('intval', array_filter(array_column($room['players'], 'userId')));
                    $room['activeUsers'] = array_values(array_filter((new UserModel())->activeUsers(), static fn (array $user): bool => ! in_array((int) $user['id'], $joinedIds, true)));
                }
                return view('games/four_player_room', ['title' => 'Oyun Odası ' . $room['code'], 'room' => $room]);
            }
            $room = (new GameRoomService())->getForPlayer($code, (int) session()->get('user_id'));
            $activeUsers = [];
            if ($room['status'] === 'waiting' && (int) $room['host']['id'] === (int) session()->get('user_id')) {
                $activeUsers = array_values(array_filter(
                    (new UserModel())->activeUsers(),
                    static fn (array $user): bool => (int) $user['id'] !== (int) session()->get('user_id')
                ));
            }
            return view('games/room', ['title' => 'Oyun Odası ' . $room['code'], 'room' => $room, 'activeUsers' => $activeUsers]);
        } catch (RuntimeException $e) {
            return redirect()->to(site_url('games/multiplayer'))->with('error', $e->getMessage());
        }
    }

    public function addBot(string $code)
    {
        try {
            (new FourPlayerGameService())->addBot($code, (int) session()->get('user_id'), (string) $this->request->getPost('difficulty'));
            return redirect()->back()->with('success', 'Bot odaya eklendi.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function removeBot(string $code, int $seat)
    {
        try {
            (new FourPlayerGameService())->removeBot($code, (int) session()->get('user_id'), $seat);
            return redirect()->back()->with('success', 'Bot odadan kaldırıldı.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function startFourPlayer(string $code)
    {
        try {(new FourPlayerGameService())->start($code,(int)session()->get('user_id'));return redirect()->back();}
        catch(RuntimeException $e){return redirect()->back()->with('error',$e->getMessage());}
    }

    public function fourPlayerState(string $code)
    {
        try{return $this->response->setJSON(['success'=>true,'room'=>(new FourPlayerGameService())->getForPlayer($code,(int)session()->get('user_id'))]);}
        catch(RuntimeException $e){return $this->response->setStatusCode(403)->setJSON(['success'=>false,'message'=>$e->getMessage()]);}
    }

    public function fourPlayerAction(string $code)
    {
        try{$input=$this->request->getJSON(true)?:$this->request->getPost();return $this->response->setJSON(['success'=>true,'room'=>(new FourPlayerGameService())->action($code,(int)session()->get('user_id'),$input),'csrfHash'=>csrf_hash()]);}
        catch(RuntimeException $e){return $this->response->setStatusCode(422)->setJSON(['success'=>false,'message'=>$e->getMessage(),'csrfHash'=>csrf_hash()]);}
    }

    public function fourPlayerRematch(string $code)
    {
        try{return $this->response->setJSON(['success'=>true,'room'=>(new FourPlayerGameService())->rematch($code,(int)session()->get('user_id')),'csrfHash'=>csrf_hash()]);}
        catch(RuntimeException $e){return $this->response->setStatusCode(422)->setJSON(['success'=>false,'message'=>$e->getMessage(),'csrfHash'=>csrf_hash()]);}
    }

    public function state(string $code)
    {
        try {
            $userId = (int) session()->get('user_id');
            session_write_close();
            return $this->response->setJSON(['success' => true, 'room' => (new GameRoomService())->getForPlayer($code, $userId)]);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function version(string $code)
    {
        try {
            $userId = (int) session()->get('user_id');
            session_write_close();
            return $this->response->setJSON(['success' => true] + (new GameRoomService())->versionForPlayer($code, $userId));
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function move(string $code)
    {
        try {
            $userId = (int) session()->get('user_id');
            session_write_close();
            $room = (new GameRoomService())->move($code, $userId, $this->request->getPost());
            return $this->response->setJSON(['success' => true, 'room' => $room, 'csrfHash' => csrf_hash()]);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'message' => $e->getMessage(), 'csrfHash' => csrf_hash()]);
        }
    }

    public function rematch(string $code)
    {
        try {
            $userId = (int) session()->get('user_id');
            session_write_close();
            $result = (new GameRoomService())->rematch($code, $userId);

            return $this->response->setJSON([
                'success' => true,
                'room' => $result['room'],
                'started' => $result['started'],
                'csrfHash' => csrf_hash(),
            ]);
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
                'csrfHash' => csrf_hash(),
            ]);
        }
    }

    public function invite(string $code, int $userId)
    {
        $currentUserId = (int) session()->get('user_id');
        $room = (new GameRoomModel())->withPlayers($code);
        if (! $room || (int) $room['host_user_id'] !== $currentUserId || $room['status'] !== 'waiting') {
            return redirect()->back()->with('error', 'Bu odadan davet gönderemezsiniz.');
        }

        $fourPlayer = in_array($room['game'], ['okey101', 'monopoly'], true);
        if ($fourPlayer) {
            $players = (new \App\Models\GameRoomPlayerModel())->forRoom((int) $room['id']);
            $memberIds = array_map(static fn (array $player): int => (int) ($player['user_id'] ?? 0), $players);
            if (count($players) >= 4 || in_array($userId, $memberIds, true)) {
                return redirect()->back()->with('error', 'Kullanıcı zaten odada veya oda dolu.');
            }
        } elseif ($room['guest_user_id'] !== null) {
            return redirect()->back()->with('error', 'Bu oda dolu.');
        }

        $target = (new UserModel())->where('id', $userId)->where('is_active', 1)
            ->where('last_seen_at >=', date('Y-m-d H:i:s', strtotime('-90 seconds')))->first();
        if (! $target || $userId === $currentUserId) {
            return redirect()->back()->with('error', 'Davet edilecek kullanıcı şu anda aktif değil.');
        }

        $key = 'game_invite:' . $room['id'] . ':' . $userId;
        $notificationModel = new NotificationModel();
        $existing = $notificationModel->where('notification_key', $key)->first();
        if ($existing && empty($existing['read_at'])) {
            return redirect()->back()->with('success', $target['username'] . ' kullanıcısının bekleyen bir daveti zaten var.');
        }
        if ($existing) {
            $notificationModel->delete($existing['id']);
        }
        $data = [
            'user_id' => $userId,
            'actor_user_id' => $currentUserId,
            'game_room_id' => (int) $room['id'],
            'type' => 'game_invite',
            'message' => session()->get('username') . ' sizi ' . match ($room['game']) {
                'sudoku' => 'Sudoku',
                'okey101' => '101 Okey',
                'monopoly' => 'Monopoly',
                'snake' => 'Yılan Yarışı',
                default => 'Mayın Tarlası',
            } . ' oyununa davet etti.',
            'target_path' => 'games/room/' . $room['code'],
            'notification_key' => $key,
            'read_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        if ($notificationModel->insert($data) === false) {
            return redirect()->back()->with('error', 'Oyun daveti kaydedilemedi. Lütfen tekrar deneyin.');
        }

        return redirect()->back()->with('success', $target['username'] . ' kullanıcısına oyun daveti gönderildi.');
    }

    public function leave(string $code)
    {
        (new GameRoomService())->leave($code, (int) session()->get('user_id'));
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'csrfHash' => csrf_hash()]);
        }
        return redirect()->to(site_url('games/multiplayer'));
    }
}
