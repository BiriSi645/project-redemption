<?php

use App\Libraries\NotificationActionPolicy;
use CodeIgniter\Test\CIUnitTestCase;

final class NotificationActionPolicyTest extends CIUnitTestCase
{
    public function testGameInviteCannotBeAcceptedThroughGetRedirect(): void
    {
        $target = NotificationActionPolicy::safeGetTarget([
            'type' => 'game_invite',
            'target_path' => 'games/room/ABC123',
        ]);

        $this->assertSame('notifications', $target);
    }

    public function testSafeInternalTargetIsPreserved(): void
    {
        $this->assertSame('tasks/12/edit', NotificationActionPolicy::safeGetTarget([
            'type' => 'task_due',
            'target_path' => 'tasks/12/edit',
        ]));
    }

    public function testExternalOrMalformedTargetIsRejected(): void
    {
        $this->assertSame('notifications', NotificationActionPolicy::safeGetTarget([
            'type' => 'admin_announcement',
            'target_path' => 'https://example.com',
        ]));
        $this->assertSame('notifications', NotificationActionPolicy::safeGetTarget([
            'type' => 'admin_announcement',
            'target_path' => '../admin',
        ]));
    }
}
