<?php

use CodeIgniter\Test\CIUnitTestCase;

final class AccountDeletionProjectSafetyTest extends CIUnitTestCase
{
    public function testSharedOwnedProjectsAreCheckedBeforeUserDeletion(): void
    {
        $source = file_get_contents(APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . 'Profile.php');
        $methodStart = strpos($source, 'public function delete()');
        $method = substr($source, $methodStart);

        $projectGuard = strpos($method, "->table('projects')");
        $userDelete = strpos($method, '(new UserModel())->skipValidation(true)->delete($userId)');

        $this->assertNotFalse($projectGuard);
        $this->assertNotFalse($userDelete);
        $this->assertLessThan($userDelete, $projectGuard);
        $this->assertStringContainsString("project_members.status', 'accepted'", $method);
        $this->assertStringContainsString("project_members.user_id !=', \$userId", $method);
    }
}
