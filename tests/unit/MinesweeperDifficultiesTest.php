<?php

use App\Libraries\MinesweeperDifficulties;
use CodeIgniter\Test\CIUnitTestCase;

final class MinesweeperDifficultiesTest extends CIUnitTestCase
{
    public function testAllFiveModesHaveValidPlayableDimensions(): void
    {
        $modes = MinesweeperDifficulties::all();
        $this->assertSame(['beginner', 'medium', 'expert', 'master', 'nightmare'], array_keys($modes));

        foreach ($modes as $mode) {
            $cells = $mode['rows'] * $mode['cols'];
            $this->assertGreaterThan(0, $mode['rows']);
            $this->assertGreaterThan(0, $mode['cols']);
            $this->assertGreaterThan(0, $mode['mines']);
            $this->assertLessThan($cells, $mode['mines']);
            $this->assertNotSame('', $mode['subtitle']);
        }
    }

    public function testOnlyKnownModesAreAcceptedForScores(): void
    {
        $this->assertTrue(MinesweeperDifficulties::has('master'));
        $this->assertTrue(MinesweeperDifficulties::has('nightmare'));
        $this->assertFalse(MinesweeperDifficulties::has('Master'));
        $this->assertFalse(MinesweeperDifficulties::has('unknown'));
    }

    public function testMasterIsWideAndNightmareRemainsSquare(): void
    {
        $modes = MinesweeperDifficulties::all();

        $this->assertSame(16, $modes['master']['rows']);
        $this->assertSame(32, $modes['master']['cols']);
        $this->assertSame(32, $modes['nightmare']['rows']);
        $this->assertSame(32, $modes['nightmare']['cols']);
    }
}
