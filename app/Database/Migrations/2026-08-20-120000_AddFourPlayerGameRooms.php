<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFourPlayerGameRooms extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('settings', 'game_rooms')) {
            $this->forge->addColumn('game_rooms', [
                'settings' => ['type' => 'LONGTEXT', 'null' => true, 'after' => 'difficulty'],
            ]);
        }
        if (! $this->db->fieldExists('max_players', 'game_rooms')) {
            $this->forge->addColumn('game_rooms', [
                'max_players' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 2, 'after' => 'settings'],
            ]);
        }

        if (! $this->db->tableExists('game_room_players')) {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'room_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'seat_index' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true],
            'player_type' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'human'],
            'display_name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'bot_difficulty' => ['type' => 'VARCHAR', 'constraint' => 12, 'null' => true],
            'team_no' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'null' => true],
            'is_ready' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'last_seen_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['room_id', 'seat_index']);
        $this->forge->addUniqueKey(['room_id', 'user_id']);
        $this->forge->addKey(['user_id', 'last_seen_at']);
        $this->forge->addForeignKey('room_id', 'game_rooms', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('game_room_players');
        }

        if (! $this->db->tableExists('okey101_scores')) {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'room_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'team_no' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'null' => true],
            'round_no' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true],
            'penalty_score' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'result' => ['type' => 'VARCHAR', 'constraint' => 12],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'created_at']);
        $this->forge->addKey(['room_id', 'round_no']);
        $this->forge->addForeignKey('room_id', 'game_rooms', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('okey101_scores');
        }
    }

    public function down()
    {
        $this->forge->dropTable('okey101_scores', true);
        $this->forge->dropTable('game_room_players', true);
        $this->forge->dropColumn('game_rooms', ['settings', 'max_players']);
    }
}
