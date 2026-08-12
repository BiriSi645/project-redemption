<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNoteCommentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'note_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'content' => [
                'type' => 'TEXT',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['note_id', 'created_at']);
        $this->forge->addKey(['user_id', 'created_at']);
        $this->forge->addForeignKey('note_id', 'notes', 'id', 'CASCADE', 'CASCADE', 'fk_note_comments_note');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_note_comments_user');
        $this->forge->createTable('note_comments');
    }

    public function down()
    {
        $this->forge->dropTable('note_comments');
    }
}
