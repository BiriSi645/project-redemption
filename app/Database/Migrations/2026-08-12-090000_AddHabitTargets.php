<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHabitTargets extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('target_count', 'habits')) {
            $this->forge->addColumn('habits', [
                'target_count' => [
                    'type'       => 'INT',
                    'constraint' => 3,
                    'unsigned'   => true,
                    'default'    => 1,
                    'after'      => 'frequency',
                ],
            ]);
        }
        $this->db->query('ALTER TABLE habits MODIFY target_count INT UNSIGNED NOT NULL DEFAULT 1');

        if (! $this->db->fieldExists('completed_on', 'habit_completions')) {
            $this->forge->addColumn('habit_completions', [
                'completed_on' => [
                    'type'  => 'DATE',
                    'null'  => true,
                    'after' => 'period_key',
                ],
            ]);
        }

        $this->db->query('UPDATE habit_completions SET completed_on = DATE(completed_at) WHERE completed_on IS NULL');
        if (! $this->indexExists('habit_completions', 'uq_habit_day')) {
            $this->db->query('ALTER TABLE habit_completions ADD UNIQUE KEY uq_habit_day (habit_id, completed_on)');
        }
        if ($this->indexExists('habit_completions', 'uq_habit_period')) {
            $this->forge->dropKey('habit_completions', 'uq_habit_period');
        }
        $this->db->query('ALTER TABLE habit_completions MODIFY completed_on DATE NOT NULL');
    }

    public function down()
    {
        $this->forge->dropKey('habit_completions', 'uq_habit_day');
        $this->db->query('ALTER TABLE habit_completions ADD UNIQUE KEY uq_habit_period (habit_id, period_key)');
        $this->forge->dropColumn('habit_completions', 'completed_on');
        $this->forge->dropColumn('habits', 'target_count');
    }

    private function indexExists(string $table, string $name): bool
    {
        return $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name])->getNumRows() > 0;
    }
}
