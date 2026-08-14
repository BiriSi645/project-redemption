<?php

namespace Tests\Unit;

use App\Libraries\RealtimeSessionAuthenticator;
use CodeIgniter\Test\CIUnitTestCase;

class RealtimeSessionAuthenticatorTest extends CIUnitTestCase
{
    private string $sessionPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'realtime-session-' . bin2hex(random_bytes(6));
        mkdir($this->sessionPath, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->sessionPath . DIRECTORY_SEPARATOR . '*') ?: [] as $file) unlink($file);
        @rmdir($this->sessionPath);
        parent::tearDown();
    }

    public function testAuthenticatesValidLoggedInSession(): void
    {
        $id = str_repeat('a', 32);
        file_put_contents($this->sessionPath . DIRECTORY_SEPARATOR . 'ci_session' . $id, 'logged_in|b:1;user_id|i:42;username|s:5:"Ceyda";');
        $result = (new RealtimeSessionAuthenticator($this->sessionPath))->authenticate($id);
        $this->assertSame(['userId' => 42, 'username' => 'Ceyda'], $result);
    }

    public function testRejectsInvalidAndLoggedOutSessions(): void
    {
        $authenticator = new RealtimeSessionAuthenticator($this->sessionPath);
        $this->assertNull($authenticator->authenticate('../invalid'));
        $id = str_repeat('b', 32);
        file_put_contents($this->sessionPath . DIRECTORY_SEPARATOR . 'ci_session' . $id, 'logged_in|b:0;user_id|i:7;');
        $this->assertNull($authenticator->authenticate($id));
    }
}
