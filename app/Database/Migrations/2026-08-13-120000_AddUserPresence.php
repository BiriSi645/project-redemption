<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserPresence extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('last_seen_at', 'users')) {
            $this->forge->addColumn('users', ['last_seen_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'is_active']]);
        }
        if (! $this->indexExists('users', 'idx_users_active_seen')) {
            $this->db->query('ALTER TABLE `users` ADD INDEX `idx_users_active_seen` (`is_active`, `last_seen_at`)');
        }
    }

    public function down()
    {
        if ($this->indexExists('users', 'idx_users_active_seen')) $this->db->query('ALTER TABLE `users` DROP INDEX `idx_users_active_seen`');
        if ($this->db->fieldExists('last_seen_at', 'users')) $this->forge->dropColumn('users', 'last_seen_at');
    }

    private function indexExists(string $table, string $name): bool
    {
        return $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name])->getNumRows() > 0;
    }
}
