<?php

namespace App\Models;

use CodeIgniter\Model;

class GameScoreModel extends Model
{
    protected $table = 'game_scores';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['user_id', 'game', 'difficulty', 'score'];

    public function recordBest(int $userId, string $game, string $difficulty, int $score): array
    {
        $current = $this->where('user_id', $userId)->where('game', $game)->where('difficulty', $difficulty)->first();
        $higherIsBetter = in_array(
            $game,
            ['snake', 'tetris'],
            true
        );

        $improved = $current === null
            || (
                $higherIsBetter
                    ? $score > (int) $current['score']
                    : $score < (int) $current['score']
            );

        if ($improved) {
            $data = ['user_id' => $userId, 'game' => $game, 'difficulty' => $difficulty, 'score' => $score];
            $current === null ? $this->insert($data) : $this->update((int) $current['id'], $data);
        }

        return [
            'improved' => $improved,
            'personalBest' => $improved ? $score : (int) $current['score'],
            'leaderboard' => $this->leaderboard($game, $difficulty),
        ];
    }

    public function leaderboard(string $game, string $difficulty, int $limit = 3): array
    {
        return $this->select('game_scores.score, game_scores.updated_at, users.username')
            ->join('users', 'users.id = game_scores.user_id')
            ->where('game_scores.game', $game)
            ->where('game_scores.difficulty', $difficulty)
            ->orderBy(
                'game_scores.score',
                in_array($game, ['snake', 'tetris'], true)
                    ? 'DESC'
                    : 'ASC'
            )
            ->orderBy('game_scores.updated_at', 'ASC')
            ->limit($limit)
            ->findAll();
    }

    public function personalBest(int $userId, string $game, string $difficulty): ?int
    {
        $row = $this->select('score')->where('user_id', $userId)->where('game', $game)->where('difficulty', $difficulty)->first();

        return $row === null ? null : (int) $row['score'];
    }
}
