<?php

use CodeIgniter\Test\CIUnitTestCase;

final class RegisterRateLimitSecurityTest extends CIUnitTestCase
{
    public function testRegisterIsRateLimitedBeforeUserCreation(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . 'Auth.php');
        $methodStart = strpos($source, 'public function storeRegister()');
        $nextMethod = strpos($source, 'public function login()', $methodStart);
        $method = substr($source, $methodStart, $nextMethod - $methodStart);

        $rateLimit = strpos($method, "rateLimited('register'");
        $insert = strpos($method, '$userModel->insert($data)');

        $this->assertNotFalse($rateLimit);
        $this->assertNotFalse($insert);
        $this->assertLessThan($insert, $rateLimit);
        $this->assertStringContainsString('REGISTER_RATE_MAX_ATTEMPTS', $method);
        $this->assertStringContainsString('auth.register_rate_limited', $method);
    }
}
