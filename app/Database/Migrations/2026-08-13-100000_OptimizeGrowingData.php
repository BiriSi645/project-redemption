<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class OptimizeGrowingData extends Migration
{
    private const ADD = [
        'habit_completions' => [
            'idx_habit_period' => ['habit_id', 'period_key'],
        ],
        'game_rooms' => [
            'idx_game_rooms_status_updated' => ['status', 'updated_at'],
        ],
        'notes' => [
            'idx_notes_public_created' => ['is_public', 'created_at'],
            'idx_notes_created' => ['created_at'],
        ],
    ];

    public function up()
    {
        foreach (self::ADD as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (! $this->indexExists($table, $name)) {
                    $this->db->query(sprintf('ALTER TABLE `%s` ADD INDEX `%s` (`%s`)', $table, $name, implode('`, `', $columns)));
                }
            }
        }

        $this->dropIfExists('tasks', 'user_id_status');
        $this->dropIfExists('audit_logs', 'status_code');
    }

    public function down()
    {
        foreach (self::ADD as $table => $indexes) {
            foreach (array_keys($indexes) as $name) $this->dropIfExists($table, $name);
        }
        if (! $this->indexExists('tasks', 'user_id_status')) $this->db->query('ALTER TABLE `tasks` ADD INDEX `user_id_status` (`user_id`, `status`)');
        if (! $this->indexExists('audit_logs', 'status_code')) $this->db->query('ALTER TABLE `audit_logs` ADD INDEX `status_code` (`status_code`)');
    }

    private function indexExists(string $table, string $name): bool
    {
        return $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name])->getNumRows() > 0;
    }

    private function dropIfExists(string $table, string $name): void
    {
        if ($this->indexExists($table, $name)) $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
    }
}
