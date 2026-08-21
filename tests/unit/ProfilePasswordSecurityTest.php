<?php

use CodeIgniter\Test\CIUnitTestCase;

final class ProfilePasswordSecurityTest extends CIUnitTestCase
{
    public function testPasswordChangeRevokesOutstandingResetToken(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . 'Profile.php');
        $methodStart = strpos($source, 'public function password()');
        $nextMethod = strpos($source, 'public function requestEmailChange()', $methodStart);
        $method = substr($source, $methodStart, $nextMethod - $methodStart);

        $this->assertStringContainsString("'password_reset_token' => null", $method);
        $this->assertStringContainsString("'password_reset_expires_at' => null", $method);
    }
}
