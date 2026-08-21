<?php

use CodeIgniter\Test\CIUnitTestCase;

final class AuthSessionRevocationTest extends CIUnitTestCase
{
    public function testLoginStoresCurrentAuthVersionInSession(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . 'Auth.php');

        $this->assertStringContainsString(
            "'auth_version' => (int) (\$user['auth_version'] ?? 1)",
            $source
        );
    }

    public function testPasswordResetIncrementsAuthVersion(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . 'Auth.php');

        $this->assertStringContainsString(
            "'auth_version' => (int) (\$user['auth_version'] ?? 1) + 1",
            $source
        );
    }

    public function testAuthFilterRejectsAStaleSessionVersion(): void
    {
        $source = file_get_contents(APPPATH . 'Filters' . DIRECTORY_SEPARATOR . 'AuthFilter.php');

        $this->assertStringContainsString("select('id,is_active,role,auth_version')", $source);
        $this->assertStringContainsString("session()->get('auth_version')", $source);
        $this->assertStringContainsString("session()->destroy()", $source);
        $this->assertStringNotContainsString("Şifreniz değiştirildiği için", $source);
    }
}
