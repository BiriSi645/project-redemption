<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class PreserveDirectMessagesAfterAccountDeletion extends Migration
{
    private const COLUMNS = [
        ['direct_conversations', 'user_one_id'],
        ['direct_conversations', 'user_two_id'],
        ['direct_messages', 'sender_id'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as [$table, $column]) {
            $this->dropUserForeignKey($table, $column);
            $this->db->query("ALTER TABLE `{$table}` MODIFY `{$column}` INT UNSIGNED NULL");
            $this->db->query(
                "ALTER TABLE `{$table}` ADD CONSTRAINT `fk_{$table}_{$column}_preserve` "
                . "FOREIGN KEY (`{$column}`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL"
            );
        }
    }

    public function down(): void
    {
        // A rollback cannot restore deleted users. Remove orphaned records
        // before returning to the old non-nullable, cascading schema.
        $this->db->table('direct_messages')->where('sender_id', null)->delete();
        $this->db->table('direct_conversations')
            ->groupStart()->where('user_one_id', null)->orWhere('user_two_id', null)->groupEnd()
            ->delete();

        foreach (array_reverse(self::COLUMNS) as [$table, $column]) {
            $this->dropUserForeignKey($table, $column);
            $this->db->query("ALTER TABLE `{$table}` MODIFY `{$column}` INT UNSIGNED NOT NULL");
            $this->db->query(
                "ALTER TABLE `{$table}` ADD CONSTRAINT `fk_{$table}_{$column}_cascade` "
                . "FOREIGN KEY (`{$column}`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE"
            );
        }
    }

    private function dropUserForeignKey(string $table, string $column): void
    {
        $row = $this->db->query(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? '
            . "AND REFERENCED_TABLE_NAME = 'users' LIMIT 1",
            [$table, $column]
        )->getRowArray();

        if ($row !== null) {
            $constraint = str_replace('`', '``', (string) $row['CONSTRAINT_NAME']);
            $this->db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }
}
