<?php

use CodeIgniter\Test\CIUnitTestCase;

final class VerificationResendEnumerationTest extends CIUnitTestCase
{
    public function testEveryResendOutcomeUsesTheSameExternalResponse(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . 'Auth.php');
        $methodStart = strpos($source, 'public function resendVerification()');
        $nextMethod = strpos($source, 'public function sendPasswordReset()', $methodStart);
        $method = substr($source, $methodStart, $nextMethod - $methodStart);

        $this->assertSame(8, substr_count($method, 'return $this->genericVerificationResendResponse();'));
        $this->assertStringNotContainsString('zaten doğrulanmış', $method);
        $this->assertStringNotContainsString("->with('error'", $method);
        $this->assertStringNotContainsString("site_url('verify-email')", $method);
    }

    public function testGenericResponseDoesNotRevealAccountState(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . 'Auth.php');

        $this->assertStringContainsString("->to(site_url('login'))", $source);
        $this->assertStringContainsString('Hesap uygunsa doğrulama kodu gönderildi.', $source);
    }
}
