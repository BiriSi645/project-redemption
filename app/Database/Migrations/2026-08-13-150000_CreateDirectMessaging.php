<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDirectMessaging extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],
            'user_one_id' => ['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'user_two_id' => ['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'last_message_at' => ['type'=>'DATETIME','null'=>true],
            'created_at' => ['type'=>'DATETIME','null'=>true],
            'updated_at' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_one_id','user_two_id'], 'uq_direct_conversation_users');
        $this->forge->addKey(['user_one_id','last_message_at'], false, false, 'idx_conversations_user_one');
        $this->forge->addKey(['user_two_id','last_message_at'], false, false, 'idx_conversations_user_two');
        $this->forge->addForeignKey('user_one_id','users','id','CASCADE','CASCADE');
        $this->forge->addForeignKey('user_two_id','users','id','CASCADE','CASCADE');
        $this->forge->createTable('direct_conversations');

        $this->forge->addField([
            'id' => ['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],
            'conversation_id' => ['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'sender_id' => ['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'body' => ['type'=>'VARCHAR','constraint'=>2000],
            'read_at' => ['type'=>'DATETIME','null'=>true],
            'deleted_by_sender' => ['type'=>'TINYINT','constraint'=>1,'default'=>0],
            'deleted_by_recipient' => ['type'=>'TINYINT','constraint'=>1,'default'=>0],
            'created_at' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['conversation_id','id'], false, false, 'idx_direct_messages_conversation');
        $this->forge->addKey(['conversation_id','sender_id','read_at'], false, false, 'idx_direct_messages_unread');
        $this->forge->addForeignKey('conversation_id','direct_conversations','id','CASCADE','CASCADE');
        $this->forge->addForeignKey('sender_id','users','id','CASCADE','CASCADE');
        $this->forge->createTable('direct_messages');

        $this->forge->addField([
            'id' => ['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],
            'blocker_id' => ['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'blocked_id' => ['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'created_at' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['blocker_id','blocked_id'], 'uq_user_block');
        $this->forge->addKey('blocked_id');
        $this->forge->addForeignKey('blocker_id','users','id','CASCADE','CASCADE');
        $this->forge->addForeignKey('blocked_id','users','id','CASCADE','CASCADE');
        $this->forge->createTable('user_blocks');
    }

    public function down()
    {
        $this->forge->dropTable('user_blocks');
        $this->forge->dropTable('direct_messages');
        $this->forge->dropTable('direct_conversations');
    }
}
