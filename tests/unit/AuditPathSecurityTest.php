<?php

namespace Tests\Unit;

use App\Libraries\AuditLogger;
use CodeIgniter\Test\CIUnitTestCase;

final class AuditPathSecurityTest extends CIUnitTestCase
{
    public function testPasswordResetTokenIsRedacted(): void
    {
        $token = str_repeat('a', 64);
        $this->assertSame('reset-password/[REDACTED]', AuditLogger::sanitizePath('reset-password/' . $token));
        $this->assertSame('reset-password/[REDACTED]', AuditLogger::sanitizePath('/index.php/reset-password/' . $token . '/'));
        $this->assertStringNotContainsString($token, AuditLogger::sanitizePath('reset-password/' . $token));
    }

    public function testOrdinaryAuditPathIsNotChanged(): void
    {
        $this->assertSame('notes/42', AuditLogger::sanitizePath('notes/42'));
    }

    public function testRedactedResetSubmissionHasSpecificAuditAction(): void
    {
        [$action] = AuditLogger::describePath('reset-password/secret-token');
        $this->assertSame('auth.password_reset_submit', $action);
    }
}
