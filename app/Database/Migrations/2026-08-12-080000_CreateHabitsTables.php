<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHabitsTables extends Migration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'frequency' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'daily',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
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
        $this->forge->addKey(['user_id', 'is_active']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_habits_user');
        $this->forge->createTable('habits');

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'habit_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'period_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['habit_id', 'period_key'], 'uq_habit_period');
        $this->forge->addKey(['user_id', 'completed_at']);
        $this->forge->addForeignKey('habit_id', 'habits', 'id', 'CASCADE', 'CASCADE', 'fk_habit_completions_habit');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_habit_completions_user');
        $this->forge->createTable('habit_completions');
    }

    public function down()
    {
        $this->forge->dropTable('habit_completions');
        $this->forge->dropTable('habits');
    }
}
