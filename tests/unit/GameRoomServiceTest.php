<?php

use App\Libraries\GameRoomService;
use CodeIgniter\Test\CIUnitTestCase;

final class GameRoomServiceTest extends CIUnitTestCase
{
    public function testChordLosesWhenCorrectNumberOfFlagsAreInWrongPlaces(): void
    {
        $state = $this->mineState([8]);
        $this->move($state, ['index' => 4, 'action' => 'chord']);
        $this->assertTrue($state['lost']);
        $this->assertTrue($state['completed']);
    }

    public function testChordRevealsNeighborsWhenMineIsFlagged(): void
    {
        $state = $this->mineState([0]);
        $this->move($state, ['index' => 4, 'action' => 'chord']);
        $this->assertFalse($state['lost']);
        $this->assertTrue($state['completed']);
        $this->assertCount(8, $state['revealed']);
    }

    public function testSharedSudokuEndsAfterThreeWrongMoves(): void
    {
        $puzzle = App\Libraries\SudokuPuzzles::get('beginner');
        $state = [
            'puzzle' => $puzzle['puzzle'], 'solution' => $puzzle['solution'],
            'values' => str_split($puzzle['puzzle']), 'owners' => array_fill(0, 81, null),
            'mistakes' => 0, 'failed' => false, 'completed' => false, 'completedAt' => null,
        ];
        $method = new ReflectionMethod(GameRoomService::class, 'sudokuMove');
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $arguments = [&$state, 1, ['index' => 2, 'number' => 1]];
            $method->invokeArgs(new GameRoomService(), $arguments);
            $this->assertSame($attempt, $state['mistakes']);
        }
        $this->assertTrue($state['failed']);
        $this->assertTrue($state['completed']);
    }

    private function move(array &$state, array $input): void
    {
        $method = new ReflectionMethod(GameRoomService::class, 'minesMove');
        $arguments = [&$state, 1, $input];
        $method->invokeArgs(new GameRoomService(), $arguments);
    }

    private function mineState(array $flags): array
    {
        return [
            'rows' => 3, 'cols' => 3, 'mines' => 1, 'mineIndexes' => [0],
            'revealed' => [4], 'revealOwners' => ['4' => 1], 'flags' => $flags,
            'flagOwners' => [(string) $flags[0] => 1], 'startedAt' => time(),
            'completed' => false, 'lost' => false, 'completedAt' => null,
        ];
    }
}
