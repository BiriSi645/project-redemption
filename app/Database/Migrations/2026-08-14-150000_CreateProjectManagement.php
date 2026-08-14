<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectManagement extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],
            'owner_id'=>['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'name'=>['type'=>'VARCHAR','constraint'=>120],
            'description'=>['type'=>'TEXT','null'=>true],
            'color'=>['type'=>'VARCHAR','constraint'=>7,'default'=>'#2563eb'],
            'created_at'=>['type'=>'DATETIME','null'=>true], 'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['owner_id','updated_at']);
        $this->forge->addForeignKey('owner_id','users','id','CASCADE','CASCADE'); $this->forge->createTable('projects');

        $this->forge->addField([
            'id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],
            'project_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'user_id'=>['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'invited_by'=>['type'=>'INT','constraint'=>11,'unsigned'=>true,'null'=>true],
            'role'=>['type'=>'VARCHAR','constraint'=>20,'default'=>'member'],
            'status'=>['type'=>'VARCHAR','constraint'=>20,'default'=>'pending'],
            'responded_at'=>['type'=>'DATETIME','null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true], 'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addUniqueKey(['project_id','user_id'],'uq_project_member'); $this->forge->addKey(['user_id','status']);
        $this->forge->addForeignKey('project_id','projects','id','CASCADE','CASCADE');
        $this->forge->addForeignKey('user_id','users','id','CASCADE','CASCADE');
        $this->forge->addForeignKey('invited_by','users','id','SET NULL','CASCADE'); $this->forge->createTable('project_members');

        $this->forge->addField([
            'id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true,'auto_increment'=>true],
            'project_id'=>['type'=>'BIGINT','constraint'=>20,'unsigned'=>true],
            'created_by'=>['type'=>'INT','constraint'=>11,'unsigned'=>true,'null'=>true],
            'assigned_to'=>['type'=>'INT','constraint'=>11,'unsigned'=>true,'null'=>true],
            'title'=>['type'=>'VARCHAR','constraint'=>160], 'description'=>['type'=>'TEXT','null'=>true],
            'status'=>['type'=>'VARCHAR','constraint'=>20,'default'=>'todo'], 'due_date'=>['type'=>'DATE','null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true], 'updated_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['project_id','status','due_date']); $this->forge->addKey(['assigned_to','status']);
        $this->forge->addForeignKey('project_id','projects','id','CASCADE','CASCADE');
        $this->forge->addForeignKey('created_by','users','id','SET NULL','CASCADE');
        $this->forge->addForeignKey('assigned_to','users','id','SET NULL','CASCADE'); $this->forge->createTable('project_items');
    }

    public function down()
    {
        $this->forge->dropTable('project_items'); $this->forge->dropTable('project_members'); $this->forge->dropTable('projects');
    }
}
