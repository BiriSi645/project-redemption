<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectSections extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'project_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['project_id', 'name']);
        $this->forge->addKey(['project_id', 'sort_order']);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('project_sections');

        $this->forge->addColumn('project_items', [
            'section_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
                'after' => 'assigned_to',
            ],
        ]);
        $this->db->query('CREATE INDEX idx_project_items_section ON project_items (project_id, section_id)');
        $this->db->query('ALTER TABLE project_items ADD CONSTRAINT fk_project_items_section FOREIGN KEY (section_id) REFERENCES project_sections(id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE project_items DROP FOREIGN KEY fk_project_items_section');
        $this->db->query('DROP INDEX idx_project_items_section ON project_items');
        $this->forge->dropColumn('project_items', 'section_id');
        $this->forge->dropTable('project_sections');
    }
}
