<?php

use CodeIgniter\Test\CIUnitTestCase;

final class DirectMessageRetentionTest extends CIUnitTestCase
{
    public function testMessagingForeignKeysPreserveHistoryAfterUserDeletion(): void
    {
        $migration = file_get_contents(
            APPPATH . 'Database' . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR
            . '2026-08-21-110000_PreserveDirectMessagesAfterAccountDeletion.php'
        );

        $this->assertStringContainsString("['direct_conversations', 'user_one_id']", $migration);
        $this->assertStringContainsString("['direct_conversations', 'user_two_id']", $migration);
        $this->assertStringContainsString("['direct_messages', 'sender_id']", $migration);
        $this->assertStringContainsString('ON DELETE SET NULL', $migration);
    }

    public function testConversationQueriesRetainDeletedParticipantAndSenderRows(): void
    {
        $model = file_get_contents(APPPATH . 'Models' . DIRECTORY_SEPARATOR . 'DirectConversationModel.php');
        $controller = file_get_contents(APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . 'Messages.php');

        $this->assertStringContainsString("'Silinmiş kullanıcı'", $model);
        $this->assertStringContainsString("'users user_one', 'user_one.id = direct_conversations.user_one_id', 'left'", $model);
        $this->assertStringContainsString('dm.sender_id IS NULL', $model);
        $this->assertStringContainsString("orWhere('sender_id', null)", $controller);
        $this->assertStringContainsString("['id'=>null, 'username'=>'Silinmiş kullanıcı'", $controller);
    }
}
