<?php

use CodeIgniter\Test\CIUnitTestCase;

final class SelfDeletePrivacyTest extends CIUnitTestCase
{
    public function testSelfDeleteRemovesPersonalAuditRowsBeforeUserDeletion(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . 'Profile.php');
        $methodStart = strpos($source, 'public function delete()');
        $method = substr($source, $methodStart);

        $auditDelete = strpos($method, "->table('audit_logs')->where('user_id', \$userId)->delete()");
        $userDelete = strpos($method, '(new UserModel())->skipValidation(true)->delete($userId)');

        $this->assertNotFalse($auditDelete);
        $this->assertNotFalse($userDelete);
        $this->assertLessThan($userDelete, $auditDelete);
        $this->assertStringContainsString('$db->transStart()', $method);
        $this->assertStringContainsString('$db->transComplete()', $method);
    }

    public function testAuditFilterDoesNotRecreateAnonymousSelfDeleteLog(): void
    {
        $source = file_get_contents(APPPATH . 'Filters' . DIRECTORY_SEPARATOR . 'AuditLogFilter.php');

        $this->assertStringContainsString("'profile/delete'", $source);
    }
}
