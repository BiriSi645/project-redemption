<?php

namespace Tests\Unit;

use App\Controllers\GameRooms;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionMethod;

final class FourPlayerSessionLockTest extends CIUnitTestCase
{
    /** @dataProvider fourPlayerEndpointProvider */
    public function testFourPlayerEndpointReleasesSessionBeforeServiceWork(string $methodName): void
    {
        $reflection = new ReflectionMethod(GameRooms::class, $methodName);
        $lines = file($reflection->getFileName());
        $source = implode('', array_slice(
            $lines,
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1
        ));

        $userReadPosition = strpos($source, "session()->get('user_id')");
        $sessionClosePosition = strpos($source, 'session_write_close()');
        $servicePosition = strpos($source, 'new FourPlayerGameService()');

        $this->assertNotFalse($userReadPosition);
        $this->assertNotFalse($sessionClosePosition);
        $this->assertNotFalse($servicePosition);
        $this->assertGreaterThan($userReadPosition, $sessionClosePosition);
        $this->assertLessThan($servicePosition, $sessionClosePosition);
    }

    public static function fourPlayerEndpointProvider(): array
    {
        return [
            'state' => ['fourPlayerState'],
            'action' => ['fourPlayerAction'],
            'rematch' => ['fourPlayerRematch'],
        ];
    }
}
