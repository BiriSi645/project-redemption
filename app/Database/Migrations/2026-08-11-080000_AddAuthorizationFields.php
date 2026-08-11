<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAuthorizationFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'role' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'user',
                'after'      => 'password_hash',
            ],
        ]);

        $this->forge->addColumn('notes', [
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'id',
            ],
            'is_public' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'content',
            ],
        ]);

        $firstUser = $this->db->table('users')->orderBy('id', 'ASC')->get()->getRowArray();

        if ($firstUser !== null) {
            $this->db->table('users')->where('id', $firstUser['id'])->update(['role' => 'admin']);
            $this->db->table('notes')->where('user_id', null)->update(['user_id' => $firstUser['id']]);
        }

        $orphanCount = $this->db->table('notes')->where('user_id', null)->countAllResults();

        if ($orphanCount === 0) {
            $this->forge->modifyColumn('notes', [
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false,
                ],
            ]);

            $this->db->query(
                'ALTER TABLE notes ADD CONSTRAINT fk_notes_user '
                . 'FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE'
            );
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('user_id', 'notes')) {
            $foreignKeys = $this->db->getForeignKeyData('notes');

            if (isset($foreignKeys['fk_notes_user'])) {
                $this->forge->dropForeignKey('notes', 'fk_notes_user');
            }

            $this->forge->dropColumn('notes', ['user_id', 'is_public']);
        }

        if ($this->db->fieldExists('role', 'users')) {
            $this->forge->dropColumn('users', 'role');
        }
    }
}
