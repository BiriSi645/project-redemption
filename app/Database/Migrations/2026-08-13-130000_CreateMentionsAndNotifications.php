<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMentionsAndNotifications extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],
            'note_id' => ['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'user_id' => ['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'created_at' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['note_id','user_id'], 'uq_note_mention');
        $this->forge->addKey(['user_id','created_at']);
        $this->forge->addForeignKey('note_id','notes','id','CASCADE','CASCADE');
        $this->forge->addForeignKey('user_id','users','id','CASCADE','CASCADE');
        $this->forge->createTable('note_mentions');

        $this->forge->addField([
            'id' => ['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],
            'user_id' => ['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'actor_user_id' => ['type'=>'INT','constraint'=>11,'unsigned'=>true,'null'=>true],
            'note_id' => ['type'=>'INT','constraint'=>11,'unsigned'=>true,'null'=>true],
            'type' => ['type'=>'VARCHAR','constraint'=>30],
            'message' => ['type'=>'VARCHAR','constraint'=>255],
            'read_at' => ['type'=>'DATETIME','null'=>true],
            'created_at' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id','read_at','created_at'], false, false, 'idx_notifications_inbox');
        $this->forge->addForeignKey('user_id','users','id','CASCADE','CASCADE');
        $this->forge->addForeignKey('actor_user_id','users','id','SET NULL','CASCADE');
        $this->forge->addForeignKey('note_id','notes','id','CASCADE','CASCADE');
        $this->forge->createTable('notifications');
    }

    public function down()
    {
        $this->forge->dropTable('notifications');
        $this->forge->dropTable('note_mentions');
    }
}
