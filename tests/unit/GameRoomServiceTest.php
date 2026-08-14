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

    public function testSnakePlayerWhoHitsWallLosesImmediately(): void
    {
        $state = $this->snakeState();
        $state['snakes']['host'] = [['x' => 23, 'y' => 4], ['x' => 22, 'y' => 4], ['x' => 21, 'y' => 4]];
        $state['directions']['host'] = 'right';

        $this->snakeStep($state);

        $this->assertTrue($state['completed']);
        $this->assertSame(2, $state['winnerId']);
        $this->assertSame(1, $state['loserId']);
        $this->assertSame('collision', $state['reason']);
    }

    public function testLongerSnakeWinsWhenTargetLengthIsReached(): void
    {
        $state = $this->snakeState();
        $state['snakes']['host'] = array_map(static fn (int $x): array => ['x' => $x, 'y' => 4], range(16, 3));
        $state['directions']['host'] = 'right';
        $state['food'] = ['x' => 17, 'y' => 4];

        $this->snakeStep($state);

        $this->assertCount(15, $state['snakes']['host']);
        $this->assertTrue($state['completed']);
        $this->assertSame(1, $state['winnerId']);
        $this->assertSame('target', $state['reason']);
    }

    private function move(array &$state, array $input): void
    {
        $method = new ReflectionMethod(GameRoomService::class, 'minesMove');
        $arguments = [&$state, 1, $input];
        $method->invokeArgs(new GameRoomService(), $arguments);
    }

    private function snakeStep(array &$state): void
    {
        $method = new ReflectionMethod(GameRoomService::class, 'snakeStep');
        $arguments = [&$state, 1, 2];
        $method->invokeArgs(new GameRoomService(), $arguments);
    }

    private function snakeState(): array
    {
        return [
            'grid' => 24, 'targetLength' => 15,
            'snakes' => [
                'host' => [['x'=>5,'y'=>7],['x'=>4,'y'=>7],['x'=>3,'y'=>7]],
                'guest' => [['x'=>18,'y'=>16],['x'=>19,'y'=>16],['x'=>20,'y'=>16]],
            ],
            'directions' => ['host'=>'right','guest'=>'left'], 'food' => ['x'=>12,'y'=>12],
            'completed' => false, 'completedAt' => null, 'winnerId' => null, 'loserId' => null, 'reason' => null,
        ];
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
