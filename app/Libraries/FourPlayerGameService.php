<?php

namespace App\Libraries;

use App\Models\GameRoomModel;
use App\Models\GameRoomPlayerModel;
use RuntimeException;

final class FourPlayerGameService
{
    private const GAMES = ['okey101', 'monopoly'];
    private const BOT_LEVELS = ['normal', 'hard'];

    public function create(int $ownerId, string $game, array $input): array
    {
        if (! in_array($game, self::GAMES, true)) {
            throw new RuntimeException('Geçersiz dört kişilik oyun.');
        }

        $mode = $game === 'okey101' && ($input['okey_mode'] ?? '') === 'teams' ? 'teams' : 'individual';
        $settings = $game === 'okey101'
            ? ['mode' => $mode, 'pairs' => true, 'indicator' => true, 'hand_finish' => true]
            : ['freeParkingPool' => ! empty($input['free_parking']), 'auctions' => true, 'trades' => true];

        $user = db_connect()->table('users')->select('username')->where('id', $ownerId)->get()->getRowArray();
        $username = $user['username'] ?? null;
        if (! $username) {
            throw new RuntimeException('Kullanıcı bulunamadı.');
        }

        $db = db_connect();
        $db->transBegin();
        try {
            $roomModel = new GameRoomModel();
            $roomId = false;
            $code = '';
            for ($attempt = 0; $attempt < 8 && $roomId === false; $attempt++) {
                $code = $this->roomCode();
                try {
                    $roomId = $roomModel->insert([
                        'code' => $code,
                        'game' => $game,
                        'difficulty' => 'standard',
                        'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
                        'max_players' => 4,
                        'host_user_id' => $ownerId,
                        'host_room_seen_at' => date('Y-m-d H:i:s'),
                        'status' => 'waiting',
                        'state' => json_encode($this->waitingState($game), JSON_UNESCAPED_UNICODE),
                        'version' => 1,
                    ], true);
                } catch (\Throwable) {
                    $roomId = false;
                }
            }
            if ($roomId === false) {
                throw new RuntimeException('Oda oluşturulamadı.');
            }

            (new GameRoomPlayerModel())->insert([
                'room_id' => $roomId,
                'user_id' => $ownerId,
                'seat_index' => 0,
                'player_type' => 'human',
                'display_name' => $username,
                'team_no' => $mode === 'teams' ? 1 : null,
                'is_ready' => 1,
                'last_seen_at' => date('Y-m-d H:i:s'),
            ]);
            $db->transCommit();
            return $this->getForPlayer($code, $ownerId);
        } catch (\Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }
    }

    public function join(int $userId, string $code): array
    {
        $code = strtoupper(trim($code));
        $db = db_connect();
        $db->transBegin();
        try {
            $room = $db->query('SELECT * FROM game_rooms WHERE code = ? FOR UPDATE', [$code])->getRowArray();
            if (! $room || ! in_array($room['game'], self::GAMES, true)) {
                throw new RuntimeException('Oda bulunamadı.');
            }
            $players = (new GameRoomPlayerModel())->forRoom((int) $room['id']);
            foreach ($players as $player) {
                if ((int) ($player['user_id'] ?? 0) === $userId) {
                    $db->transCommit();
                    return $this->getForPlayer($code, $userId);
                }
            }
            if ($room['status'] !== 'waiting' || count($players) >= 4) {
                throw new RuntimeException('Bu oda dolu veya oyun başlamış.');
            }
            $seat = $this->firstFreeSeat($players);
            $settings = json_decode((string) $room['settings'], true) ?: [];
            $user = $db->table('users')->select('username')->where('id', $userId)->get()->getRowArray();
            $username = $user['username'] ?? null;
            if (! $username) {
                throw new RuntimeException('Kullanıcı bulunamadı.');
            }
            (new GameRoomPlayerModel())->insert([
                'room_id' => $room['id'], 'user_id' => $userId, 'seat_index' => $seat,
                'player_type' => 'human', 'display_name' => $username,
                'team_no' => ($settings['mode'] ?? '') === 'teams' ? ($seat % 2) + 1 : null,
                'is_ready' => 1, 'last_seen_at' => date('Y-m-d H:i:s'),
            ]);
            $this->bump((int) $room['id'], (int) $room['version']);
            $db->transCommit();
            return $this->getForPlayer($code, $userId);
        } catch (\Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }
    }

    public function addBot(string $code, int $ownerId, string $difficulty): array
    {
        if (! in_array($difficulty, self::BOT_LEVELS, true)) {
            $difficulty = 'normal';
        }
        $db = db_connect();
        $db->transBegin();
        try {
            $room = $this->lockedOwnerRoom($code, $ownerId);
            $players = (new GameRoomPlayerModel())->forRoom((int) $room['id']);
            if (count($players) >= 4) {
                throw new RuntimeException('Odada boş koltuk yok.');
            }
            $seat = $this->firstFreeSeat($players);
            $settings = json_decode((string) $room['settings'], true) ?: [];
            (new GameRoomPlayerModel())->insert([
                'room_id' => $room['id'], 'user_id' => null, 'seat_index' => $seat,
                'player_type' => 'bot', 'display_name' => 'Bot ' . ($seat + 1),
                'bot_difficulty' => $difficulty,
                'team_no' => ($settings['mode'] ?? '') === 'teams' ? ($seat % 2) + 1 : null,
                'is_ready' => 1, 'last_seen_at' => date('Y-m-d H:i:s'),
            ]);
            $this->bump((int) $room['id'], (int) $room['version']);
            $db->transCommit();
            return $this->getForPlayer($code, $ownerId);
        } catch (\Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }
    }

    public function removeBot(string $code, int $ownerId, int $seat): array
    {
        $db = db_connect();
        $db->transBegin();
        try {
            $room = $this->lockedOwnerRoom($code, $ownerId);
            $deleted = (new GameRoomPlayerModel())->where([
                'room_id' => $room['id'], 'seat_index' => $seat, 'player_type' => 'bot',
            ])->delete();
            if (! $deleted) {
                throw new RuntimeException('Bu koltukta kaldırılabilecek bot yok.');
            }
            $this->bump((int) $room['id'], (int) $room['version']);
            $db->transCommit();
            return $this->getForPlayer($code, $ownerId);
        } catch (\Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }
    }

    public function start(string $code, int $ownerId): array
    {
        $db=db_connect();$db->transBegin();
        try{$room=$this->lockedOwnerRoom($code,$ownerId);$players=(new GameRoomPlayerModel())->forRoom((int)$room['id']);
            if(count($players)!==4)throw new RuntimeException('Oyunu başlatmak için dört koltuk da dolmalı.');
            $settings=json_decode((string)$room['settings'],true)?:[];
            $state=$room['game']==='okey101'?(new Okey101Engine())->create($players):(new MonopolyEngine())->create($players,$settings);
            $db->table('game_rooms')->where('id',$room['id'])->update(['state'=>json_encode($state,JSON_UNESCAPED_UNICODE),'status'=>'playing','version'=>(int)$room['version']+1,'updated_at'=>date('Y-m-d H:i:s')]);
            $db->transCommit();return $this->getForPlayer($code,$ownerId);
        }catch(\Throwable $e){$db->transRollback();throw $e;}
    }

    public function action(string $code,int $userId,array $input):array
    {
        $db=db_connect();$db->transBegin();
        try{$room=$db->query('SELECT * FROM game_rooms WHERE code = ? FOR UPDATE',[strtoupper($code)])->getRowArray();
            if(!$room||!in_array($room['game'],self::GAMES,true)||$room['status']!=='playing')throw new RuntimeException('Oyun aktif değil.');
            $players=(new GameRoomPlayerModel())->forRoom((int)$room['id']);$seat=null;foreach($players as $p)if((int)($p['user_id']??0)===$userId)$seat=(int)$p['seat_index'];if($seat===null)throw new RuntimeException('Bu odada değilsiniz.');
            $state=json_decode($room['state'],true);$engine=$room['game']==='okey101'?new Okey101Engine():new MonopolyEngine();$engine->act($state,$seat,(string)($input['action']??''),$input);
            for($guard=0;$guard<12&&$state['phase']==='playing';$guard++){ $current=(int)$state['turn'];$player=null;foreach($players as $p)if((int)$p['seat_index']===$current)$player=$p;if(!$player||$player['player_type']!=='bot')break;$engine->botTurn($state,$current); }
            $status=$state['phase']==='completed'?'completed':'playing';$db->table('game_rooms')->where('id',$room['id'])->update(['state'=>json_encode($state,JSON_UNESCAPED_UNICODE),'status'=>$status,'version'=>(int)$room['version']+1,'updated_at'=>date('Y-m-d H:i:s')]);
            $db->transCommit();return $this->getForPlayer($code,$userId);
        }catch(\Throwable $e){$db->transRollback();throw $e;}
    }

    public function rematch(string $code,int $userId):array
    {
        $db=db_connect();$db->transBegin();
        try{$room=$db->query('SELECT * FROM game_rooms WHERE code = ? FOR UPDATE',[strtoupper($code)])->getRowArray();if(!$room||$room['status']!=='completed'||!in_array($room['game'],self::GAMES,true))throw new RuntimeException('Bu oyun yeniden başlatılamaz.');$players=(new GameRoomPlayerModel())->forRoom((int)$room['id']);$seat=null;foreach($players as $p)if((int)($p['user_id']??0)===$userId)$seat=(int)$p['seat_index'];if($seat===null)throw new RuntimeException('Bu odada değilsiniz.');$state=json_decode($room['state'],true)?:[];$ready=$state['rematchReady']??[];$ready[$seat]=true;$all=true;foreach($players as $p)if($p['player_type']==='human'&&empty($ready[(int)$p['seat_index']]))$all=false;if($all){$settings=json_decode((string)$room['settings'],true)?:[];$state=$room['game']==='okey101'?(new Okey101Engine())->create($players):(new MonopolyEngine())->create($players,$settings);$status='playing';}else{$state['rematchReady']=$ready;$status='completed';}$db->table('game_rooms')->where('id',$room['id'])->update(['state'=>json_encode($state,JSON_UNESCAPED_UNICODE),'status'=>$status,'version'=>(int)$room['version']+1,'updated_at'=>date('Y-m-d H:i:s')]);$db->transCommit();return $this->getForPlayer($code,$userId);}catch(\Throwable $e){$db->transRollback();throw $e;}
    }

    public function getForPlayer(string $code, int $userId): array
    {
        $room = (new GameRoomModel())->where('code', strtoupper($code))->first();
        if (! $room || ! in_array($room['game'], self::GAMES, true)) {
            throw new RuntimeException('Oda bulunamadı.');
        }
        $players = (new GameRoomPlayerModel())->forRoom((int) $room['id']);
        $current = null;
        foreach ($players as $player) {
            if ((int) ($player['user_id'] ?? 0) === $userId) {
                $current = $player;
                break;
            }
        }
        if ($current === null) {
            throw new RuntimeException('Bu odaya erişemezsiniz.');
        }
        (new GameRoomPlayerModel())->update($current['id'], ['last_seen_at' => date('Y-m-d H:i:s')]);

        $state = json_decode((string) $room['state'], true) ?: [];
        if ($room['game'] === 'okey101' && isset($state['hands'])) {
            $seat=(int)$current['seat_index'];$own=$state['hands'][$seat]??[];
            $state['handCounts']=array_map('count',$state['hands']);unset($state['hands']);$state['hand']=$own;
        }
        if ($room['game'] === 'monopoly') $state['spaces'] = MonopolyEngine::spaces();
        return [
            'id' => (int) $room['id'], 'code' => $room['code'], 'game' => $room['game'],
            'status' => $room['status'], 'version' => (int) $room['version'],
            'settings' => json_decode((string) $room['settings'], true) ?: [],
            'state' => $state,
            'players' => array_map(static fn (array $player): array => [
                'seat' => (int) $player['seat_index'],
                'userId' => $player['user_id'] === null ? null : (int) $player['user_id'],
                'name' => $player['display_name'], 'type' => $player['player_type'],
                'difficulty' => $player['bot_difficulty'],
                'team' => $player['team_no'] === null ? null : (int) $player['team_no'],
            ], $players),
            'currentUserId' => $userId,
            'isOwner' => (int) $room['host_user_id'] === $userId,
        ];
    }

    public function isFourPlayerGame(string $code): bool
    {
        $room = (new GameRoomModel())->select('game')->where('code', strtoupper($code))->first();
        return in_array($room['game'] ?? '', self::GAMES, true);
    }

    private function lockedOwnerRoom(string $code, int $ownerId): array
    {
        $room = db_connect()->query('SELECT * FROM game_rooms WHERE code = ? FOR UPDATE', [strtoupper($code)])->getRowArray();
        if (! $room || (int) $room['host_user_id'] !== $ownerId || $room['status'] !== 'waiting') {
            throw new RuntimeException('Bu oda ayarını yalnızca oda sahibi değiştirebilir.');
        }
        return $room;
    }

    private function waitingState(string $game): array
    {
        return ['phase' => 'waiting', 'game' => $game, 'round' => 1, 'log' => []];
    }

    private function firstFreeSeat(array $players): int
    {
        $used = array_fill(0, 4, false);
        foreach ($players as $player) {
            $used[(int) $player['seat_index']] = true;
        }
        foreach ($used as $seat => $occupied) {
            if (! $occupied) return $seat;
        }
        throw new RuntimeException('Odada boş koltuk yok.');
    }

    private function bump(int $roomId, int $version): void
    {
        (new GameRoomModel())->skipValidation(true)->update($roomId, [
            'version' => $version + 1, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function roomCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        return $code;
    }
}
