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

        $settings = $this->settingsForGame($game, $input);
        $mode = $settings['mode'] ?? 'individual';

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
            $this->runBotTurnChain($state, $players, $engine);
            $status=$state['phase']==='completed'?'completed':'playing';$db->table('game_rooms')->where('id',$room['id'])->update(['state'=>json_encode($state,JSON_UNESCAPED_UNICODE),'status'=>$status,'version'=>(int)$room['version']+1,'updated_at'=>date('Y-m-d H:i:s')]);
            $db->transCommit();return $this->getForPlayer($code,$userId);
        }catch(\Throwable $e){$db->transRollback();throw $e;}
    }

    public function rematch(string $code,int $userId):array
    {
        $db=db_connect();$db->transBegin();
        try{$room=$db->query('SELECT * FROM game_rooms WHERE code = ? FOR UPDATE',[strtoupper($code)])->getRowArray();if(!$room||$room['status']!=='completed'||!in_array($room['game'],self::GAMES,true))throw new RuntimeException('Bu oyun yeniden başlatılamaz.');$players=(new GameRoomPlayerModel())->forRoom((int)$room['id']);$seat=null;foreach($players as $p)if((int)($p['user_id']??0)===$userId)$seat=(int)$p['seat_index'];if($seat===null)throw new RuntimeException('Bu odada değilsiniz.');$state=json_decode($room['state'],true)?:[];$ready=$state['rematchReady']??[];$ready[$seat]=true;$all=$this->allHumanPlayersReady($players,$ready);if($all){$settings=json_decode((string)$room['settings'],true)?:[];$state=$room['game']==='okey101'?(new Okey101Engine())->create($players):(new MonopolyEngine())->create($players,$settings);$status='playing';}else{$state['rematchReady']=$ready;$status='completed';}$db->table('game_rooms')->where('id',$room['id'])->update(['state'=>json_encode($state,JSON_UNESCAPED_UNICODE),'status'=>$status,'version'=>(int)$room['version']+1,'updated_at'=>date('Y-m-d H:i:s')]);$db->transCommit();return $this->getForPlayer($code,$userId);}catch(\Throwable $e){$db->transRollback();throw $e;}
    }

    /**
     * Removes a human from a four-player room without using the legacy
     * host/guest presence columns.
     *
     * Waiting seats are released. Once a game has started, the seat is
     * converted to a bot so the engine's fixed four-seat state cannot stall.
     */
    public function leave(string $code, int $userId): array
    {
        $db = db_connect();
        $db->transBegin();

        try {
            $room = $db->query(
                'SELECT * FROM game_rooms WHERE code = ? FOR UPDATE',
                [strtoupper(trim($code))]
            )->getRowArray();

            if (! $room || ! in_array($room['game'], self::GAMES, true)) {
                throw new RuntimeException('Oda bulunamadı.');
            }

            $playerModel = new GameRoomPlayerModel();
            $players = $playerModel->forRoom((int) $room['id']);
            $leavingPlayer = null;

            foreach ($players as $player) {
                if ((int) ($player['user_id'] ?? 0) === $userId && $player['player_type'] === 'human') {
                    $leavingPlayer = $player;
                    break;
                }
            }

            if ($leavingPlayer === null) {
                throw new RuntimeException('Bu odada değilsiniz.');
            }

            $remainingHumans = array_values(array_filter(
                $players,
                static fn (array $player): bool =>
                    $player['player_type'] === 'human'
                    && (int) ($player['user_id'] ?? 0) !== $userId
            ));

            if ($room['status'] === 'waiting') {
                $playerModel->delete((int) $leavingPlayer['id']);

                if ($remainingHumans === []) {
                    $db->table('game_rooms')->where('id', $room['id'])->delete();
                    $db->transCommit();

                    return [
                        'deleted' => true,
                        'code' => $room['code'],
                        'game' => $room['game'],
                        'status' => 'deleted',
                        'version' => (int) $room['version'] + 1,
                        'userIds' => [],
                    ];
                }

                $updates = [
                    'version' => (int) $room['version'] + 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                if ((int) $room['host_user_id'] === $userId) {
                    $updates['host_user_id'] = (int) $remainingHumans[0]['user_id'];
                    $updates['host_room_seen_at'] = date('Y-m-d H:i:s');
                }
                $db->table('game_rooms')->where('id', $room['id'])->update($updates);
            } else {
                if ($remainingHumans === []) {
                    $db->table('game_rooms')->where('id', $room['id'])->delete();
                    $db->transCommit();

                    return [
                        'deleted' => true,
                        'code' => $room['code'],
                        'game' => $room['game'],
                        'status' => 'deleted',
                        'version' => (int) $room['version'] + 1,
                        'userIds' => [],
                    ];
                }

                $playerModel->skipValidation(true)->update((int) $leavingPlayer['id'], [
                    'user_id' => null,
                    'player_type' => 'bot',
                    'display_name' => $leavingPlayer['display_name'] . ' (Bot)',
                    'bot_difficulty' => 'normal',
                    'is_ready' => 1,
                    'last_seen_at' => null,
                ]);

                $updates = [
                    'version' => (int) $room['version'] + 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                if ((int) $room['host_user_id'] === $userId && $remainingHumans !== []) {
                    $updates['host_user_id'] = (int) $remainingHumans[0]['user_id'];
                    $updates['host_room_seen_at'] = date('Y-m-d H:i:s');
                }
                $db->table('game_rooms')->where('id', $room['id'])->update($updates);
            }

            $db->transCommit();

            return [
                'deleted' => false,
                'code' => $room['code'],
                'game' => $room['game'],
                'status' => $room['status'],
                'version' => (int) $room['version'] + 1,
                'userIds' => array_values(array_map(
                    static fn (array $player): int => (int) $player['user_id'],
                    $remainingHumans
                )),
            ];
        } catch (\Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }
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
            $state = $this->projectOkeyStateForSeat($state, (int) $current['seat_index']);
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

    private function runBotTurnChain(array &$state, array $players, Okey101Engine|MonopolyEngine $engine): void
    {
        for ($guard=0; $guard<40 && ($state['phase']??null)==='playing'; $guard++) {
            $current=!empty($state['trade'])
                ? (int)$state['trade']['to']
                : (!empty($state['auction'])?(int)$state['auction']['next']:(int)$state['turn']);$player=null;
            foreach($players as $candidate)if((int)$candidate['seat_index']===$current){$player=$candidate;break;}
            if(!$player||$player['player_type']!=='bot')return;
            $engine->botTurn($state,$current);
        }
    }

    private function allHumanPlayersReady(array $players, array $ready): bool
    {
        foreach($players as $player)if($player['player_type']==='human'&&empty($ready[(int)$player['seat_index']]))return false;
        return true;
    }

    private function settingsForGame(string $game, array $input): array
    {
        if($game==='okey101')return ['mode'=>($input['okey_mode']??'')==='teams'?'teams':'individual','pairs'=>true,'indicator'=>true,'hand_finish'=>true];
        return ['freeParkingPool'=>!empty($input['free_parking']),'auctions'=>true,'trades'=>true];
    }

    private function projectOkeyStateForSeat(array $state, int $seat): array
    {
        $hands = $state['hands'] ?? [];
        $state['hand'] = $hands[$seat] ?? [];
        $state['handCounts'] = array_map('count', $hands);
        $state['deckCount'] = count($state['deck'] ?? []);

        unset($state['hands'], $state['deck']);

        return $state;
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
