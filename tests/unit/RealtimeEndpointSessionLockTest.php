<?php

namespace Tests\Unit;

use App\Controllers\LiveUpdates;
use App\Controllers\Messages;
use App\Controllers\Notifications;
use App\Controllers\Presence;
use App\Controllers\Realtime;
use App\Controllers\UpdateStatus;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionMethod;

final class RealtimeEndpointSessionLockTest extends CIUnitTestCase
{
    /** @dataProvider endpointProvider */
    public function testEndpointReleasesSessionBeforeLongRunningWork(
        string $controller,
        string $methodName,
        string $workMarker
    ): void {
        $source = $this->methodSource($controller, $methodName);
        $closePosition = strpos($source, 'session_write_close()');
        $workPosition = strpos($source, $workMarker);

        $this->assertNotFalse($closePosition);
        $this->assertNotFalse($workPosition);
        $this->assertLessThan($workPosition, $closePosition);
    }

    public function testRealtimeTokenCopiesSessionValuesBeforeClosingSession(): void
    {
        $source = $this->methodSource(Realtime::class, 'token');
        $closePosition = strpos($source, 'session_write_close()');

        foreach (["session()->get('user_id')", "session()->get('logged_in')", "session()->get('username')"] as $read) {
            $this->assertLessThan($closePosition, strpos($source, $read));
        }

        $this->assertStringNotContainsString('session()->get(', substr($source, $closePosition + 21));
    }

    public static function endpointProvider(): array
    {
        return [
            'heartbeat' => [Presence::class, 'heartbeat', 'setStatusCode(204)'],
            'active users' => [Presence::class, 'activeUsers', 'new \\App\\Models\\UserModel()'],
            'live updates' => [LiveUpdates::class, 'status', 'cleanupInactiveRooms()'],
            'notification preview' => [Notifications::class, 'preview', 'createDueSoonNotifications($userId)'],
            'message preview' => [Messages::class, 'preview', 'new DirectConversationModel()'],
            'realtime token' => [Realtime::class, 'token', 'new RealtimeTokenService()'],
            'version' => [UpdateStatus::class, 'version', 'new CodeVersion()'],
        ];
    }

    private function methodSource(string $controller, string $methodName): string
    {
        $method = new ReflectionMethod($controller, $methodName);
        $lines = file($method->getFileName());

        return implode('', array_slice(
            $lines,
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1
        ));
    }
}
