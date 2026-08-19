<?php

namespace App\Controllers;

use App\Models\GameScoreModel;
use App\Libraries\SudokuPuzzles;
use App\Libraries\MinesweeperDifficulties;

class Games extends BaseController
{
    public function index(): string
    {
        return view('games/index', [
            'title' => 'Oyunlar',
        ]);
    }

    public function snake(): string
    {
        $userId = (int) session()->get('user_id');

        $scoreModel = new GameScoreModel();

        return view('games/snake', [
            'title' => 'Yılan Oyunu',
            'userId' => $userId,
            'personalBest' =>
                $scoreModel->personalBest(
                    $userId,
                    'snake',
                    'default'
                ) ?? 0,
            'leaderboard' =>
                $scoreModel->leaderboard(
                    'snake',
                    'default'
                ),
        ]);
    }

    public function minesweeper(): string
    {
        $userId = (int) session()->get('user_id');

        $scoreModel = new GameScoreModel();

        $leaderboards = [];
        $personalBests = [];

        $difficulties =
            MinesweeperDifficulties::all();

        foreach (
            array_keys($difficulties)
            as $difficulty
        ) {
            $leaderboards[$difficulty] =
                $scoreModel->leaderboard(
                    'minesweeper',
                    $difficulty
                );

            $personalBests[$difficulty] =
                $scoreModel->personalBest(
                    $userId,
                    'minesweeper',
                    $difficulty
                );
        }

        return view(
            'games/minesweeper',
            [
                'title' => 'Mayın Tarlası',
                'userId' => $userId,
                'leaderboards' => $leaderboards,
                'personalBests' => $personalBests,
                'difficulties' => $difficulties,
            ]
        );
    }

    public function sudoku(): string
    {
        $userId = (int) session()->get('user_id');

        $scoreModel = new GameScoreModel();

        $leaderboards = [];
        $personalBests = [];

        foreach (
            [
                'beginner',
                'medium',
                'expert',
            ] as $difficulty
        ) {
            $leaderboards[$difficulty] =
                $scoreModel->leaderboard(
                    'sudoku',
                    $difficulty
                );

            $personalBests[$difficulty] =
                $scoreModel->personalBest(
                    $userId,
                    'sudoku',
                    $difficulty
                );
        }

        return view(
            'games/sudoku',
            [
                'title' => 'Sudoku',
                'leaderboards' => $leaderboards,
                'personalBests' => $personalBests,
                'puzzles' => SudokuPuzzles::all(),
            ]
        );
    }

    public function sudokuPuzzle(string $difficulty)
    {
        if (! SudokuPuzzles::has($difficulty)) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'success' => false,
                    'message' =>
                        'Geçersiz Sudoku zorluğu.',
                ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'puzzle' =>
                SudokuPuzzles::random(
                    $difficulty
                ),
        ]);
    }

    public function saveScore()
    {
        $game =
            (string) $this->request
                ->getPost('game');

        $difficulty =
            (string) $this->request
                ->getPost('difficulty');

        $score = filter_var(
            $this->request->getPost('score'),
            FILTER_VALIDATE_INT
        );

        $validSnake =
            $game === 'snake'
            && $difficulty === 'default'
            && $score !== false
            && $score >= 10
            && $score <= 1000000
            && $score % 10 === 0;

        $validMines =
            $game === 'minesweeper'
            && MinesweeperDifficulties::has(
                $difficulty
            )
            && $score !== false
            && $score >= 1
            && $score <= 86400;

        $validSudoku =
            $game === 'sudoku'
            && in_array(
                $difficulty,
                [
                    'beginner',
                    'medium',
                    'expert',
                ],
                true
            )
            && $score !== false
            && $score >= 1
            && $score <= 86400;

        if (
            ! $validSnake
            && ! $validMines
            && ! $validSudoku
        ) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Geçersiz skor.',
                ]);
        }

        $result =
            (new GameScoreModel())
                ->recordBest(
                    (int) session()->get(
                        'user_id'
                    ),
                    $game,
                    $difficulty,
                    (int) $score
                );

        return $this->response->setJSON(
            [
                'success' => true,
            ] + $result
        );
    }
}