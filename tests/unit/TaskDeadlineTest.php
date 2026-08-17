<?php

use App\Libraries\TaskDeadline;
use CodeIgniter\Test\CIUnitTestCase;

final class TaskDeadlineTest extends CIUnitTestCase
{
    public function testInterpretsTaskTimeInIstanbulTimezone(): void
    {
        $deadline = TaskDeadline::fromDatabase('2026-08-17', '12:30:00');

        $this->assertNotNull($deadline);
        $this->assertSame('Europe/Istanbul', $deadline->getTimezone()->getName());
        $this->assertSame('2026-08-17T12:30:00+03:00', $deadline->format('Y-m-d\TH:i:sP'));
    }

    public function testUsesEndOfDayWhenTaskHasNoTime(): void
    {
        $deadline = TaskDeadline::fromDatabase('2026-08-17', null);

        $this->assertNotNull($deadline);
        $this->assertSame('23:59:59', $deadline->format('H:i:s'));
    }

    public function testCanUseExplicitTimezone(): void
    {
        $deadline = TaskDeadline::fromDatabase('2026-08-17', '12:30:00', new \DateTimeZone('UTC'));

        $this->assertSame('+00:00', $deadline?->format('P'));
    }
}
