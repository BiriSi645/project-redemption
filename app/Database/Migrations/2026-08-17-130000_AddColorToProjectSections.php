<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColorToProjectSections extends Migration
{
    public function up()
    {
        $this->forge->addColumn('project_sections', [
            'color' => [
                'type' => 'VARCHAR',
                'constraint' => 7,
                'null' => false,
                'default' => '#6366f1',
                'after' => 'name',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('project_sections', 'color');
    }
}
