<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGameRoomsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 6],
            'game' => ['type' => 'VARCHAR', 'constraint' => 20],
            'difficulty' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'beginner'],
            'host_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'guest_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'waiting'],
            'state' => ['type' => 'LONGTEXT'],
            'version' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addKey(['host_user_id', 'status']);
        $this->forge->addKey(['guest_user_id', 'status']);
        $this->forge->addForeignKey('host_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('guest_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('game_rooms');
    }

    public function down()
    {
        $this->forge->dropTable('game_rooms');
    }
}
