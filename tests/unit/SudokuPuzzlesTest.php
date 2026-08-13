<?php

use App\Libraries\SudokuPuzzles;
use CodeIgniter\Test\CIUnitTestCase;

final class SudokuPuzzlesTest extends CIUnitTestCase
{
    public function testEveryPuzzleHasOneCompatibleValidSolution(): void
    {
        foreach (SudokuPuzzles::all() as $difficulty => $data) {
            $this->assertSame(81, strlen($data['puzzle']), $difficulty);
            $this->assertSame(81, strlen($data['solution']), $difficulty);
            foreach (str_split($data['puzzle']) as $index => $clue) {
                if ($clue !== '0') $this->assertSame($clue, $data['solution'][$index], "{$difficulty}: {$index}. ipucu uyuşmuyor");
            }
            $this->assertTrue($this->isValidSolution($data['solution']), "{$difficulty}: çözüm Sudoku kurallarına aykırı");
            $this->assertSame(1, $this->solutionCount($data['puzzle'], 2), "{$difficulty}: bulmacanın tek çözümü yok");
        }
    }

    private function isValidSolution(string $solution): bool
    {
        $expected = array_map('strval', range(1, 9));
        for ($index = 0; $index < 9; $index++) {
            $row = str_split(substr($solution, $index * 9, 9)); sort($row);
            $column = []; for ($r = 0; $r < 9; $r++) $column[] = $solution[$r * 9 + $index]; sort($column);
            if ($row !== $expected || $column !== $expected) return false;
        }
        for ($boxRow = 0; $boxRow < 3; $boxRow++) for ($boxColumn = 0; $boxColumn < 3; $boxColumn++) {
            $box = [];
            for ($r = 0; $r < 3; $r++) for ($c = 0; $c < 3; $c++) $box[] = $solution[(($boxRow * 3 + $r) * 9) + $boxColumn * 3 + $c];
            sort($box); if ($box !== $expected) return false;
        }
        return true;
    }

    private function solutionCount(string $puzzle, int $limit): int
    {
        $grid = array_map('intval', str_split($puzzle));
        return $this->solve($grid, $limit);
    }

    private function solve(array &$grid, int $limit): int
    {
        $bestIndex = -1; $bestCandidates = null;
        for ($index = 0; $index < 81; $index++) {
            if ($grid[$index] !== 0) continue;
            $candidates = [];
            for ($number = 1; $number <= 9; $number++) if ($this->allowed($grid, $index, $number)) $candidates[] = $number;
            if ($candidates === []) return 0;
            if ($bestCandidates === null || count($candidates) < count($bestCandidates)) {
                $bestIndex = $index; $bestCandidates = $candidates;
                if (count($candidates) === 1) break;
            }
        }
        if ($bestIndex === -1) return 1;
        $count = 0;
        foreach ($bestCandidates as $number) {
            $grid[$bestIndex] = $number;
            $count += $this->solve($grid, $limit - $count);
            if ($count >= $limit) break;
        }
        $grid[$bestIndex] = 0;
        return $count;
    }

    private function allowed(array $grid, int $index, int $number): bool
    {
        $row = intdiv($index, 9); $column = $index % 9;
        for ($i = 0; $i < 9; $i++) if ($grid[$row * 9 + $i] === $number || $grid[$i * 9 + $column] === $number) return false;
        $boxRow = intdiv($row, 3) * 3; $boxColumn = intdiv($column, 3) * 3;
        for ($r = 0; $r < 3; $r++) for ($c = 0; $c < 3; $c++) if ($grid[($boxRow + $r) * 9 + $boxColumn + $c] === $number) return false;
        return true;
    }
}
