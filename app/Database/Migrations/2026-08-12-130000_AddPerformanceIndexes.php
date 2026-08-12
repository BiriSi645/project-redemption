<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPerformanceIndexes extends Migration
{
    private const INDEXES = [
        'notes' => [
            'idx_notes_user_visibility_created' => ['user_id', 'is_public', 'created_at'],
            'idx_notes_category' => ['category'],
        ],
        'tasks' => [
            'idx_tasks_user_status_due' => ['user_id', 'status', 'due_date'],
            'idx_tasks_user_category_priority' => ['user_id', 'category', 'priority'],
        ],
        'users' => [
            'idx_users_role_active_created' => ['role', 'is_active', 'created_at'],
        ],
        'audit_logs' => [
            'idx_audit_logs_created' => ['created_at'],
            'idx_audit_logs_status_created' => ['status_code', 'created_at'],
        ],
    ];

    public function up()
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (! $this->indexExists($table, $name)) {
                    $columnList = implode('`, `', $columns);
                    $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$name}` (`{$columnList}`)");
                }
            }
        }
    }

    public function down()
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach (array_keys($indexes) as $name) {
                if ($this->indexExists($table, $name)) {
                    $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
                }
            }
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        return $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name])->getNumRows() > 0;
    }
}
