<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

final class EmailLoggingSecurityTest extends CIUnitTestCase
{
    public function testControllersNeverLogEmailDebuggerOutput(): void
    {
        $controllerFiles = glob(APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . '*.php') ?: [];

        foreach ($controllerFiles as $controllerFile) {
            $source = file_get_contents($controllerFile);

            $this->assertIsString($source);
            $this->assertStringNotContainsString(
                'printDebugger(',
                $source,
                basename($controllerFile) . ' e-posta gövdesi veya gizli kodları loglayabilir.'
            );
        }
    }

    public function testVerificationEmailIsSentOnlyOncePerAttempt(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . 'Auth.php');
        $start = strpos($source, 'private function sendVerificationCodeEmail(');

        $this->assertNotFalse($start);
        $methodSource = substr($source, $start);

        $this->assertSame(1, substr_count($methodSource, '$email->send(false)'));
    }
}
