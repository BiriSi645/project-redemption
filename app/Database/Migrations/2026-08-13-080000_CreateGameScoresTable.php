<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGameScoresTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'game' => ['type' => 'VARCHAR', 'constraint' => 30],
            'difficulty' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'default'],
            'score' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'game', 'difficulty'], 'uq_game_score_player');
        $this->forge->addKey(['game', 'difficulty', 'score'], false, false, 'idx_game_leaderboard');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_game_scores_user');
        $this->forge->createTable('game_scores');
    }

    public function down()
    {
        $this->forge->dropTable('game_scores');
    }
}
