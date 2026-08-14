<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExperienceSystem extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'experience_points' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0, 'after' => 'bio'],
        ]);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'event_type' => ['type' => 'VARCHAR', 'constraint' => 30],
            'source_key' => ['type' => 'VARCHAR', 'constraint' => 100],
            'points' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'event_type', 'source_key'], 'uq_xp_event_source');
        $this->forge->addKey(['user_id', 'event_type', 'created_at'], false, false, 'idx_xp_daily_limit');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('experience_events');
    }

    public function down()
    {
        $this->forge->dropTable('experience_events');
        $this->forge->dropColumn('users', 'experience_points');
    }
}
