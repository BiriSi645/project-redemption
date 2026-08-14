<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AddProjectItemStartDate extends Migration
{
    public function up(){ $this->forge->addColumn('project_items',['start_date'=>['type'=>'DATE','null'=>true,'after'=>'status']]);$this->db->query('CREATE INDEX idx_project_items_timeline ON project_items (project_id, start_date, due_date)'); }
    public function down(){ $this->db->query('DROP INDEX idx_project_items_timeline ON project_items');$this->forge->dropColumn('project_items','start_date'); }
}
