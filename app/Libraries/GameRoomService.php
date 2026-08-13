<?php

namespace App\Libraries;

use App\Models\GameRoomModel;
use RuntimeException;

class GameRoomService
{
    private const MINES = [
        'beginner' => ['rows' => 9, 'cols' => 9, 'mines' => 10],
        'medium' => ['rows' => 12, 'cols' => 12, 'mines' => 24],
        'expert' => ['rows' => 16, 'cols' => 16, 'mines' => 40],
    ];

    public function create(int $userId, string $game, string $difficulty): array
    {
        $this->pruneExpiredRooms();
        if (! in_array($game, ['sudoku', 'minesweeper'], true) || ! SudokuPuzzles::has($difficulty)) {
            throw new RuntimeException('Geçersiz oyun veya zorluk seviyesi.');
        }

        $state = $game === 'sudoku' ? $this->newSudoku($difficulty) : $this->newMines($difficulty);
        $model = new GameRoomModel();
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $code = $this->roomCode();
            try {
                $id = $model->insert([
                    'code' => $code, 'game' => $game, 'difficulty' => $difficulty,
                    'host_user_id' => $userId, 'status' => 'waiting',
                    'state' => json_encode($state, JSON_UNESCAPED_UNICODE), 'version' => 1,
                ], true);
                if ($id !== false) {
                    return $model->withPlayers($code);
                }
            } catch (\Throwable) {
                // Çok düşük ihtimalli oda kodu çakışmasında yeni kod üret.
            }
        }
        throw new RuntimeException('Oda şu anda oluşturulamadı.');
    }

    public function join(int $userId, string $code): array
    {
        $code = strtoupper(trim($code));
        $db = db_connect();
        $db->transBegin();
        $room = $db->query('SELECT * FROM game_rooms WHERE code = ? FOR UPDATE', [$code])->getRowArray();
        if (! $room) {
            $db->transRollback();
            throw new RuntimeException('Oda bulunamadı.');
        }
        if ((int) $room['host_user_id'] !== $userId && $room['guest_user_id'] !== null && (int) $room['guest_user_id'] !== $userId) {
            $db->transRollback();
            throw new RuntimeException('Bu oda dolu.');
        }
        if ((int) $room['host_user_id'] !== $userId && $room['guest_user_id'] === null) {
            $state = json_decode($room['state'], true);
            $state['startedAt'] = time();
            $db->table('game_rooms')->where('id', $room['id'])->update([
                'guest_user_id' => $userId, 'status' => 'playing',
                'state' => json_encode($state, JSON_UNESCAPED_UNICODE),
                'version' => (int) $room['version'] + 1, 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->transCommit();
        return (new GameRoomModel())->withPlayers($code);
    }

    public function getForPlayer(string $code, int $userId): array
    {
        $room = (new GameRoomModel())->withPlayers($code);
        $this->assertParticipant($room, $userId);
        return $this->publicRoom($room, $userId);
    }

    public function versionForPlayer(string $code, int $userId): array
    {
        $room = (new GameRoomModel())
            ->select('code, host_user_id, guest_user_id, status, version')
            ->where('code', strtoupper($code))
            ->first();
        $this->assertParticipant($room, $userId);

        return ['version' => (int) $room['version'], 'status' => $room['status']];
    }

    public function move(string $code, int $userId, array $input): array
    {
        $db = db_connect();
        $db->transBegin();
        try {
        $room = $db->query('SELECT * FROM game_rooms WHERE code = ? FOR UPDATE', [strtoupper($code)])->getRowArray();
        $this->assertParticipant($room, $userId);
        if ($room['status'] !== 'playing') {
            throw new RuntimeException('Oyun henüz başlamadı veya tamamlandı.');
        }
        $state = json_decode($room['state'], true);
        if ($room['game'] === 'sudoku') {
            $this->sudokuMove($state, $userId, $input);
        } else {
            $this->minesMove($state, $userId, $input);
        }
        $status = ! empty($state['completed']) ? 'completed' : 'playing';
        $db->table('game_rooms')->where('id', $room['id'])->update([
            'state' => json_encode($state, JSON_UNESCAPED_UNICODE), 'status' => $status,
            'version' => (int) $room['version'] + 1, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transCommit();
        $fresh = (new GameRoomModel())->withPlayers(strtoupper($code));
        return $this->publicRoom($fresh, $userId);
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function newSudoku(string $difficulty): array
    {
        $data = SudokuPuzzles::get($difficulty);
        return ['puzzle' => $data['puzzle'], 'solution' => $data['solution'], 'values' => str_split($data['puzzle']), 'owners' => array_fill(0, 81, null), 'mistakes' => 0, 'failed' => false, 'startedAt' => null, 'completed' => false, 'completedAt' => null];
    }

    private function newMines(string $difficulty): array
    {
        $config = self::MINES[$difficulty];
        return $config + ['mineIndexes' => null, 'revealed' => [], 'revealOwners' => [], 'flags' => [], 'flagOwners' => [], 'startedAt' => null, 'completed' => false, 'lost' => false, 'completedAt' => null];
    }

    private function sudokuMove(array &$state, int $userId, array $input): void
    {
        $index = filter_var($input['index'] ?? null, FILTER_VALIDATE_INT);
        $number = filter_var($input['number'] ?? null, FILTER_VALIDATE_INT);
        if ($index === false || $index < 0 || $index > 80 || $number === false || $number < 1 || $number > 9 || $state['puzzle'][$index] !== '0') {
            throw new RuntimeException('Geçersiz hamle.');
        }
        if ((int) $state['solution'][$index] !== $number) {
            $state['mistakes'] = (int) ($state['mistakes'] ?? 0) + 1;
            if ($state['mistakes'] >= 3) {
                $state['failed'] = true;
                $state['completed'] = true;
                $state['completedAt'] = time();
            }
            return;
        }
        $state['values'][$index] = (string) $number;
        $state['owners'][$index] = $userId;
        if (implode('', $state['values']) === $state['solution']) {
            $state['completed'] = true;
            $state['completedAt'] = time();
        }
    }

    private function minesMove(array &$state, int $userId, array $input): void
    {
        $index = filter_var($input['index'] ?? null, FILTER_VALIDATE_INT);
        $action = (string) ($input['action'] ?? 'reveal');
        $total = $state['rows'] * $state['cols'];
        if ($index === false || $index < 0 || $index >= $total || ! in_array($action, ['reveal', 'flag', 'chord'], true)) {
            throw new RuntimeException('Geçersiz hamle.');
        }
        if ($action === 'chord') {
            if ($state['mineIndexes'] === null || ! in_array($index, $state['revealed'], true)) return;
            $neighbors = $this->neighbors($state, $index);
            $number = $this->adjacentMineCount($state, $index);
            if ($number === 0 || count(array_intersect($neighbors, $state['flags'])) !== $number) return;
            $toReveal = [];
            foreach ($neighbors as $neighbor) {
                if (in_array($neighbor, $state['flags'], true) || in_array($neighbor, $state['revealed'], true)) continue;
                if (in_array($neighbor, $state['mineIndexes'], true)) {
                    $state['lost'] = true; $state['completed'] = true; $state['completedAt'] = time();
                    return;
                }
                $toReveal[] = $neighbor;
            }
            $this->revealMineCells($state, $userId, $toReveal);
            if (count($state['revealed']) === $total - $state['mines']) {
                $state['completed'] = true; $state['completedAt'] = time();
            }
            return;
        }
        if ($state['mineIndexes'] === null) {
            $state['mineIndexes'] = $this->placeMines($state, $index);
            $state['startedAt'] = time();
        }
        if ($action === 'flag') {
            if (in_array($index, $state['revealed'], true)) return;
            $position = array_search($index, $state['flags'], true);
            if ($position === false) {
                if (count($state['flags']) >= $state['mines']) return;
                $state['flags'][] = $index;
                $state['flagOwners'][(string) $index] = $userId;
            } else {
                array_splice($state['flags'], $position, 1);
                unset($state['flagOwners'][(string) $index]);
            }
            return;
        }
        if (in_array($index, $state['flags'], true) || in_array($index, $state['revealed'], true)) return;
        if (in_array($index, $state['mineIndexes'], true)) {
            $state['lost'] = true; $state['completed'] = true; $state['completedAt'] = time();
            return;
        }
        $this->revealMineCells($state, $userId, $index);
        if (count($state['revealed']) === $total - $state['mines']) {
            $state['completed'] = true; $state['completedAt'] = time();
        }
    }

    private function revealMineCells(array &$state, int $userId, int|array $indexes): void
    {
        $queue = is_array($indexes) ? array_values($indexes) : [$indexes];
        $cursor = 0;
        $revealedLookup = array_fill_keys($state['revealed'], true);
        $flagLookup = array_fill_keys($state['flags'], true);
        $mineLookup = array_fill_keys($state['mineIndexes'], true);
        while (isset($queue[$cursor])) {
            $current = $queue[$cursor++];
            if (isset($revealedLookup[$current]) || isset($flagLookup[$current]) || isset($mineLookup[$current])) continue;
            $state['revealed'][] = $current;
            $revealedLookup[$current] = true;
            $state['revealOwners'][(string) $current] = $userId;
            if ($this->adjacentMineCount($state, $current) === 0) {
                foreach ($this->neighbors($state, $current) as $neighbor) $queue[] = $neighbor;
            }
        }
    }

    private function placeMines(array $state, int $safe): array
    {
        $forbidden = array_flip(array_merge([$safe], $this->neighbors($state, $safe)));
        $available = array_values(array_filter(range(0, $state['rows'] * $state['cols'] - 1), static fn ($i) => ! isset($forbidden[$i])));
        shuffle($available);
        return array_slice($available, 0, $state['mines']);
    }

    private function neighbors(array $state, int $index): array
    {
        $row = intdiv($index, $state['cols']); $col = $index % $state['cols']; $result = [];
        for ($y=-1;$y<=1;$y++) for ($x=-1;$x<=1;$x++) {
            if ($x===0 && $y===0) continue; $r=$row+$y; $c=$col+$x;
            if ($r>=0 && $r<$state['rows'] && $c>=0 && $c<$state['cols']) $result[]=$r*$state['cols']+$c;
        }
        return $result;
    }

    private function adjacentMineCount(array $state, int $index): int
    {
        return count(array_intersect($this->neighbors($state, $index), $state['mineIndexes']));
    }

    private function publicRoom(array $room, int $userId): array
    {
        $state = json_decode($room['state'], true);
        unset($state['solution']);
        if ($room['game'] === 'minesweeper') {
            $mineIndexes = $state['mineIndexes']; unset($state['mineIndexes']);
            $state['numbers'] = [];
            foreach ($state['revealed'] as $index) $state['numbers'][(string) $index] = count(array_intersect($this->neighbors($state, $index), $mineIndexes ?? []));
            if (! empty($state['completed'])) $state['minesFound'] = $mineIndexes;
        }
        return [
            'code' => $room['code'], 'game' => $room['game'], 'difficulty' => $room['difficulty'], 'status' => $room['status'], 'version' => (int) $room['version'],
            'host' => ['id' => (int) $room['host_user_id'], 'username' => $room['host_username']],
            'guest' => $room['guest_user_id'] ? ['id' => (int) $room['guest_user_id'], 'username' => $room['guest_username']] : null,
            'currentUserId' => $userId, 'state' => $state,
        ];
    }

    private function assertParticipant(?array $room, int $userId): void
    {
        if (! $room) throw new RuntimeException('Oda bulunamadı.');
        if ((int) $room['host_user_id'] !== $userId && (int) ($room['guest_user_id'] ?? 0) !== $userId) throw new RuntimeException('Bu odaya erişemezsiniz.');
    }

    private function roomCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; $code = '';
        for ($i=0;$i<6;$i++) $code .= $alphabet[random_int(0, strlen($alphabet)-1)];
        return $code;
    }

    private function pruneExpiredRooms(): void
    {
        $cache = cache();
        if ($cache->get('game_rooms_pruned_today')) return;

        $db = db_connect();
        $db->table('game_rooms')->groupStart()
            ->where('status', 'waiting')->where('updated_at <', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->groupEnd()->orGroupStart()
            ->whereIn('status', ['playing', 'completed'])->where('updated_at <', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->groupEnd()->delete();
        $cache->save('game_rooms_pruned_today', '1', 86400);
    }
}
