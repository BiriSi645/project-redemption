<?php

namespace App\Database\Migrations;

use App\Libraries\NullableUserForeignKeyPolicy;
use CodeIgniter\Database\Migration;

class FixNullableUserForeignKeys extends Migration
{
    private const KEYS = [
        ['announcements', 'announcements_created_by_foreign', 'created_by'],
        ['audit_logs', 'fk_audit_logs_user', 'user_id'],
        ['notifications', 'notifications_actor_user_id_foreign', 'actor_user_id'],
        ['project_members', 'project_members_invited_by_foreign', 'invited_by'],
        ['project_items', 'project_items_created_by_foreign', 'created_by'],
        ['project_items', 'project_items_assigned_to_foreign', 'assigned_to'],
    ];

    public function up()
    {
        foreach (self::KEYS as [$table, $constraint, $column]) {
            if (! $this->db->tableExists($table) || ! $this->db->fieldExists($column, $table)) {
                continue;
            }
            $this->db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
            $this->db->query("ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraint}` FOREIGN KEY (`{$column}`) REFERENCES `users` (`id`) ON UPDATE " . NullableUserForeignKeyPolicy::ON_UPDATE . ' ON DELETE ' . NullableUserForeignKeyPolicy::ON_DELETE);
        }
    }

    public function down()
    {
        foreach (array_reverse(self::KEYS) as [$table, $constraint, $column]) {
            if (! $this->db->tableExists($table) || ! $this->db->fieldExists($column, $table)) {
                continue;
            }
            $this->db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
            $this->db->query("ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraint}` FOREIGN KEY (`{$column}`) REFERENCES `users` (`id`) ON UPDATE SET NULL ON DELETE CASCADE");
        }
    }
}
