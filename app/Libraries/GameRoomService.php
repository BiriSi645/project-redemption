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

    private const SNAKE_GRID = 30;
    private const SNAKE_TARGET_LENGTH = 15;
    private const SNAKE_TICK_MS = 180;
    public function create(int $userId, string $game, string $difficulty): array
    {
        $this->cleanupInactiveRooms();
        if (! in_array($game, ['sudoku', 'minesweeper', 'snake'], true)
            || ($game !== 'snake' && ! SudokuPuzzles::has($difficulty))) {
            throw new RuntimeException('Geçersiz oyun veya zorluk seviyesi.');
        }

        $difficulty = $game === 'snake' ? 'default' : $difficulty;
        $state = match ($game) {
            'sudoku' => $this->newSudoku($difficulty),
            'minesweeper' => $this->newMines($difficulty),
            default => $this->newSnake(),
        };
        $state['round'] = 1;
        $state['rematchReady'] = ['host' => false, 'guest' => false];
        $model = new GameRoomModel();
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $code = $this->roomCode();
            try {
                $id = $model->insert([
                    'code' => $code, 'game' => $game, 'difficulty' => $difficulty,
                    'host_user_id' => $userId, 'host_room_seen_at' => date('Y-m-d H:i:s'), 'status' => 'waiting',
                    'state' => json_encode($state, JSON_UNESCAPED_UNICODE), 'version' => 1,
                ], true);
                if ($id !== false) {
                    $room = $model->withPlayers($code);
                    $this->publishRoomUpdate($room);
                    return $room;
                }
            } catch (\Throwable) {
                // Çok düşük ihtimalli oda kodu çakışmasında yeni kod üret.
            }
        }
        throw new RuntimeException('Oda şu anda oluşturulamadı.');
    }

    public function join(int $userId, string $code): array
    {
        $this->cleanupInactiveRooms();
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
            if ($room['game'] === 'snake') {
                // İki oyuncuya da oda ekrana geldikten sonra kısa bir başlangıç payı ver.
                $state['lastTickMs'] = (int) round(microtime(true) * 1000) + 1000;
            }
            $db->table('game_rooms')->where('id', $room['id'])->update([
                'guest_user_id' => $userId, 'status' => 'playing',
                'guest_room_seen_at' => date('Y-m-d H:i:s'),
                'state' => json_encode($state, JSON_UNESCAPED_UNICODE),
                'version' => (int) $room['version'] + 1, 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $db->table('notifications')->where('game_room_id', $room['id'])->where('type', 'game_invite')
                ->where('user_id !=', $userId)->delete();
            $db->table('notifications')->where('game_room_id', $room['id'])->where('type', 'game_invite')
                ->where('user_id', $userId)->set(['read_at' => date('Y-m-d H:i:s')])->update();
        }
        if ((int) $room['host_user_id'] === $userId) {
            $db->table('game_rooms')->where('id', $room['id'])->update(['host_room_seen_at' => date('Y-m-d H:i:s')]);
        } elseif ($room['guest_user_id'] !== null && (int) $room['guest_user_id'] === $userId) {
            $db->table('game_rooms')->where('id', $room['id'])->update(['guest_room_seen_at' => date('Y-m-d H:i:s')]);
        }
        $db->transCommit();
        $fresh = (new GameRoomModel())->withPlayers($code);
        $this->publishRoomUpdate($fresh);
        return $fresh;
    }

    public function getForPlayer(string $code, int $userId): array
    {
        $this->cleanupInactiveRooms();
        $room = (new GameRoomModel())->withPlayers($code);
        $this->assertParticipant($room, $userId);
        $this->touchRoomPresence((int) $room['id'], (int) $room['host_user_id'], $userId);
        return $this->publicRoom($room, $userId);
    }

    public function versionForPlayer(string $code, int $userId): array
    {
        $this->cleanupInactiveRooms();

        $game = (new GameRoomModel())
            ->select('game')
            ->where('code', strtoupper($code))
            ->first();

        if (($game['game'] ?? null) === 'snake') {
            return $this->advanceSnakeRoom($code, $userId);
        }

        $room = (new GameRoomModel())
            ->select('id, code, host_user_id, guest_user_id, status, version')
            ->where('code', strtoupper($code))
            ->first();
        $this->assertParticipant($room, $userId);
        $this->touchRoomPresence((int) $room['id'], (int) $room['host_user_id'], $userId);

        return ['version' => (int) $room['version'], 'status' => $room['status']];
    }

    public function move(string $code, int $userId, array $input): array
    {
        $this->cleanupInactiveRooms();
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
        } elseif ($room['game'] === 'minesweeper') {
            $this->minesMove($state, $userId, $input);
        } elseif ($room['game'] === 'snake') {
            // Yön değişikliğini, o ana kadar birikmiş server tick'lerinden sonra uygula.
            $this->advanceSnake($state, (int) $room['host_user_id'], (int) $room['guest_user_id']);
            if (empty($state['completed'])) {
                $this->snakeDirection($state, $room, $userId, $input);
            }
        } else {
            throw new RuntimeException('Desteklenmeyen multiplayer oyun.');
        }
        $status = ! empty($state['completed']) ? 'completed' : 'playing';
        $presenceField = (int) $room['host_user_id'] === $userId ? 'host_room_seen_at' : 'guest_room_seen_at';
        $db->table('game_rooms')->where('id', $room['id'])->update([
            'state' => json_encode($state, JSON_UNESCAPED_UNICODE), 'status' => $status,
            'version' => (int) $room['version'] + 1, 'updated_at' => date('Y-m-d H:i:s'), $presenceField => date('Y-m-d H:i:s'),
        ]);
        $db->transCommit();
        $fresh = (new GameRoomModel())->withPlayers(strtoupper($code));
        $this->publishRoomUpdate($fresh);
        return $this->publicRoom($fresh, $userId);
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function rematch(string $code, int $userId): array
    {
        $this->cleanupInactiveRooms();
        $db = db_connect();
        $db->transBegin();

        try {
            $room = $db->query(
                'SELECT * FROM game_rooms WHERE code = ? FOR UPDATE',
                [strtoupper($code)]
            )->getRowArray();
            $this->assertParticipant($room, $userId);

            if ($room['game'] === 'snake') {
                throw new RuntimeException('Yılan yeniden oynama isteği oyun sunucusu üzerinden gönderilmelidir.');
            }
            if ($room['guest_user_id'] === null) {
                throw new RuntimeException('Yeniden oynamak için iki oyuncu da odada olmalı.');
            }
            if ($room['status'] !== 'completed') {
                throw new RuntimeException('Bu tur henüz tamamlanmadı.');
            }

            $state = json_decode($room['state'], true) ?: [];
            $ready = $state['rematchReady'] ?? ['host' => false, 'guest' => false];
            $ready = [
                'host' => ! empty($ready['host']),
                'guest' => ! empty($ready['guest']),
            ];

            $role = (int) $room['host_user_id'] === $userId ? 'host' : 'guest';
            $ready[$role] = true;
            $state['rematchReady'] = $ready;
            $state['round'] = max(1, (int) ($state['round'] ?? 1));
            $started = false;
            $status = 'completed';

            if ($ready['host'] && $ready['guest']) {
                $round = $state['round'] + 1;
                $state = match ($room['game']) {
                    'sudoku' => $this->newSudoku((string) $room['difficulty']),
                    'minesweeper' => $this->newMines((string) $room['difficulty']),
                    default => throw new RuntimeException('Desteklenmeyen multiplayer oyun.'),
                };
                $state['round'] = $round;
                $state['rematchReady'] = ['host' => false, 'guest' => false];

                // Sudoku süresi tur başlar başlamaz başlasın. Mayın Tarlası'nda
                // mevcut davranış korunur ve süre ilk hücre açıldığında başlar.
                if ($room['game'] === 'sudoku') {
                    $state['startedAt'] = time();
                }

                $status = 'playing';
                $started = true;
            }

            $presenceField = (int) $room['host_user_id'] === $userId
                ? 'host_room_seen_at'
                : 'guest_room_seen_at';

            $db->table('game_rooms')->where('id', $room['id'])->update([
                'state' => json_encode($state, JSON_UNESCAPED_UNICODE),
                'status' => $status,
                'version' => (int) $room['version'] + 1,
                'updated_at' => date('Y-m-d H:i:s'),
                $presenceField => date('Y-m-d H:i:s'),
            ]);

            $db->transCommit();
            $fresh = (new GameRoomModel())->withPlayers(strtoupper($code));
            $this->publishRoomUpdate($fresh);

            return [
                'room' => $this->publicRoom($fresh, $userId),
                'started' => $started,
            ];
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function leave(string $code, int $userId): void
    {
        $room = (new GameRoomModel())->where('code', strtoupper($code))->first();
        if (! $room) {
            return;
        }
        $this->assertParticipant($room, $userId);
        $field = (int) $room['host_user_id'] === $userId ? 'host_room_seen_at' : 'guest_room_seen_at';
        (new GameRoomModel())->skipValidation(true)->update($room['id'], [$field => null]);
        $this->cleanupInactiveRooms(true);
    }

    public function cleanupInactiveRooms(bool $force = false): void
    {
        $cache = cache();
        if (! $force && $cache->get('game_rooms_presence_cleanup')) {
            return;
        }

        $cutoff = date('Y-m-d H:i:s', strtotime('-25 seconds'));
        $roomQuery = (new GameRoomModel())
            ->select('id, status, guest_user_id, host_room_seen_at, guest_room_seen_at, updated_at')
            ->whereIn('status', ['waiting', 'playing', 'completed']);
        if (! $force) {
            $roomQuery->where('updated_at <', $cutoff);
        }
        $rooms = $roomQuery->findAll();
        $deleteIds = [];
        foreach ($rooms as $room) {
            $hostGone = empty($room['host_room_seen_at']) || $room['host_room_seen_at'] < $cutoff;
            $guestGone = empty($room['guest_user_id']) || empty($room['guest_room_seen_at']) || $room['guest_room_seen_at'] < $cutoff;
            // Bitmiş bir tur, oyunculardan en az biri odada kaldığı sürece saklanır.
            // Böylece aynı oda koduyla yeniden oynanabilir. Oda ancak iki oyuncu da
            // gerçekten ayrıldığında / presence timeout olduğunda temizlenir.
            $completedCanBeDeleted = $room['status'] === 'completed' && $hostGone && $guestGone;
            if ($completedCanBeDeleted || ($room['status'] === 'waiting' && $hostGone) || ($room['status'] === 'playing' && $hostGone && $guestGone)) {
                $deleteIds[] = (int) $room['id'];
            }
        }
        $this->deleteRooms($deleteIds);
        $cache->save('game_rooms_presence_cleanup', '1', 10);
    }

    private function publishRoomUpdate(?array $room): void
    {
        if (! $room) {
            return;
        }

        (new RealtimePublisher())->user(
            [(int) $room['host_user_id'], (int) ($room['guest_user_id'] ?? 0)],
            'game-room',
            [
                'roomCode' => (string) $room['code'],
                'game' => (string) $room['game'],
                'status' => (string) $room['status'],
                'version' => (int) $room['version'],
            ]
        );
    }

    private function newSudoku(string $difficulty): array
    {
        $data = SudokuPuzzles::random($difficulty);
        return ['puzzle' => $data['puzzle'], 'solution' => $data['solution'], 'values' => str_split($data['puzzle']), 'owners' => array_fill(0, 81, null), 'mistakes' => 0, 'failed' => false, 'startedAt' => null, 'completed' => false, 'completedAt' => null];
    }

    private function newMines(string $difficulty): array
    {
        $config = self::MINES[$difficulty];
        return $config + ['mineIndexes' => null, 'revealed' => [], 'revealOwners' => [], 'flags' => [], 'flagOwners' => [], 'startedAt' => null, 'completed' => false, 'lost' => false, 'completedAt' => null];
    }

    private function newSnake(): array
    {
        return [
            'grid' => self::SNAKE_GRID,
            'targetLength' => self::SNAKE_TARGET_LENGTH,
            'snakes' => [
                'host' => [
                    ['x' => 7, 'y' => 9],
                    ['x' => 6, 'y' => 9],
                    ['x' => 5, 'y' => 9],
                ],
                'guest' => [
                    ['x' => 22, 'y' => 20],
                    ['x' => 23, 'y' => 20],
                    ['x' => 24, 'y' => 20],
                ],
            ],
            'directions' => ['host' => 'right', 'guest' => 'left'],
            'food' => ['x' => 15, 'y' => 15],
            'lastTickMs' => null,
            'startedAt' => null,
            'completed' => false,
            'completedAt' => null,
            'winnerId' => null,
            'loserId' => null,
            'reason' => null,
        ];
    }

    private function snakeDirection(array &$state, array $room, int $userId, array $input): void
    {
        $direction = (string) ($input['direction'] ?? '');
        $vectors = [
            'up' => [0, -1],
            'down' => [0, 1],
            'left' => [-1, 0],
            'right' => [1, 0],
        ];

        if (! isset($vectors[$direction])) {
            throw new RuntimeException('Geçersiz yön.');
        }

        $player = (int) $room['host_user_id'] === $userId ? 'host' : 'guest';
        $current = $state['directions'][$player];

        if ($vectors[$direction][0] === -$vectors[$current][0]
            && $vectors[$direction][1] === -$vectors[$current][1]) {
            return;
        }

        $state['directions'][$player] = $direction;
    }

    private function advanceSnakeRoom(string $code, int $userId): array
    {
        $db = db_connect();
        $db->transBegin();

        try {
            $room = $db->query(
                'SELECT * FROM game_rooms WHERE code = ? FOR UPDATE',
                [strtoupper($code)]
            )->getRowArray();

            $this->assertParticipant($room, $userId);

            $version = (int) $room['version'];
            $status = $room['status'];

            if ($room['status'] === 'playing') {
                $state = json_decode($room['state'], true);
                $beforeTick = (int) ($state['lastTickMs'] ?? 0);

                $this->advanceSnake(
                    $state,
                    (int) $room['host_user_id'],
                    (int) $room['guest_user_id']
                );

                if ((int) ($state['lastTickMs'] ?? 0) !== $beforeTick || ! empty($state['completed'])) {
                    $status = ! empty($state['completed']) ? 'completed' : 'playing';
                    $version++;

                    $db->table('game_rooms')->where('id', $room['id'])->update([
                        'state' => json_encode($state, JSON_UNESCAPED_UNICODE),
                        'status' => $status,
                        'version' => $version,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            $presenceField = (int) $room['host_user_id'] === $userId
                ? 'host_room_seen_at'
                : 'guest_room_seen_at';

            $db->table('game_rooms')->where('id', $room['id'])->update([
                $presenceField => date('Y-m-d H:i:s'),
            ]);

            $db->transCommit();

            $fresh = (new GameRoomModel())->withPlayers(strtoupper($code));

            return [
                'version' => (int) $fresh['version'],
                'status' => $fresh['status'],
                'room' => $this->publicRoom($fresh, $userId),
            ];
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function advanceSnake(array &$state, int $hostId, int $guestId): void
    {
        $now = (int) round(microtime(true) * 1000);
        $last = (int) ($state['lastTickMs'] ?? $now);
        $steps = min(8, max(0, intdiv($now - $last, self::SNAKE_TICK_MS)));

        for ($i = 0; $i < $steps && empty($state['completed']); $i++) {
            $this->snakeStep($state, $hostId, $guestId);
            $state['lastTickMs'] += self::SNAKE_TICK_MS;
        }
    }

    private function snakeStep(array &$state, int $hostId, int $guestId): void
    {
        $vectors = [
            'up' => [0, -1],
            'down' => [0, 1],
            'left' => [-1, 0],
            'right' => [1, 0],
        ];

        $heads = [];
        $eats = [];

        foreach (['host', 'guest'] as $player) {
            [$dx, $dy] = $vectors[$state['directions'][$player]];
            $heads[$player] = [
                'x' => $state['snakes'][$player][0]['x'] + $dx,
                'y' => $state['snakes'][$player][0]['y'] + $dy,
            ];
            $eats[$player] = $heads[$player] === $state['food'];
        }

        $dead = ['host' => false, 'guest' => false];

        foreach (['host', 'guest'] as $player) {
            $head = $heads[$player];

            if ($head['x'] < 0 || $head['x'] >= self::SNAKE_GRID
                || $head['y'] < 0 || $head['y'] >= self::SNAKE_GRID) {
                $dead[$player] = true;
            }

            $own = $eats[$player]
                ? $state['snakes'][$player]
                : array_slice($state['snakes'][$player], 0, -1);

            $otherPlayer = $player === 'host' ? 'guest' : 'host';
            $other = $eats[$otherPlayer]
                ? $state['snakes'][$otherPlayer]
                : array_slice($state['snakes'][$otherPlayer], 0, -1);

            foreach (array_merge($own, $other) as $part) {
                if ($part === $head) {
                    $dead[$player] = true;
                    break;
                }
            }
        }

        if ($heads['host'] === $heads['guest']
            || ($heads['host'] === $state['snakes']['guest'][0]
                && $heads['guest'] === $state['snakes']['host'][0])) {
            $dead = ['host' => true, 'guest' => true];
        }

        if ($dead['host'] || $dead['guest']) {
            $winner = $dead['host'] && ! $dead['guest']
                ? $guestId
                : ($dead['guest'] && ! $dead['host'] ? $hostId : null);

            $loser = $winner === $hostId
                ? $guestId
                : ($winner === $guestId ? $hostId : null);

            $this->finishSnake($state, $winner, $loser, 'collision');
            return;
        }

        foreach (['host', 'guest'] as $player) {
            array_unshift($state['snakes'][$player], $heads[$player]);
            if (! $eats[$player]) {
                array_pop($state['snakes'][$player]);
            }
        }

        if ($eats['host'] || $eats['guest']) {
            $state['food'] = $this->snakeFood($state);
        }

        $hostLength = count($state['snakes']['host']);
        $guestLength = count($state['snakes']['guest']);

        if (max($hostLength, $guestLength) >= self::SNAKE_TARGET_LENGTH) {
            $winner = $hostLength === $guestLength
                ? null
                : ($hostLength > $guestLength ? $hostId : $guestId);

            $this->finishSnake($state, $winner, null, 'target');
        }
    }

    private function snakeFood(array $state): array
    {
        $occupied = [];
        foreach (array_merge($state['snakes']['host'], $state['snakes']['guest']) as $part) {
            $occupied[$part['x'] . ':' . $part['y']] = true;
        }

        $free = [];
        for ($y = 0; $y < self::SNAKE_GRID; $y++) {
            for ($x = 0; $x < self::SNAKE_GRID; $x++) {
                if (! isset($occupied[$x . ':' . $y])) {
                    $free[] = ['x' => $x, 'y' => $y];
                }
            }
        }

        return $free[random_int(0, count($free) - 1)];
    }

    private function finishSnake(array &$state, ?int $winnerId, ?int $loserId, string $reason): void
    {
        $state['completed'] = true;
        $state['completedAt'] = time();
        $state['winnerId'] = $winnerId;
        $state['loserId'] = $loserId;
        $state['reason'] = $reason;
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

    private function touchRoomPresence(int $roomId, int $hostUserId, int $userId): void
    {
        $field = $hostUserId === $userId ? 'host_room_seen_at' : 'guest_room_seen_at';
        (new GameRoomModel())->skipValidation(true)->update($roomId, [$field => date('Y-m-d H:i:s')]);
    }

    private function deleteRooms(array $roomIds): void
    {
        if ($roomIds === []) {
            return;
        }
        $db = db_connect();
        $db->transStart();
        $db->table('notifications')->whereIn('game_room_id', $roomIds)->delete();
        $db->table('game_rooms')->whereIn('id', $roomIds)->set(['status' => 'completed'])->update();
        $db->table('game_rooms')->whereIn('id', $roomIds)->delete();
        $db->transComplete();
    }
}
