<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProductivityFeatures extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'theme' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'light',
                'after'      => 'role',
            ],
            'language' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'tr',
                'after'      => 'theme',
            ],
            'notifications_enabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'language',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'after'      => 'notifications_enabled',
            ],
        ]);

        $this->forge->addColumn('notes', [
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'Genel',
                'after'      => 'content',
            ],
        ]);

        $this->forge->addColumn('tasks', [
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'Genel',
                'after'      => 'description',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tasks', 'category');
        $this->forge->dropColumn('notes', 'category');
        $this->forge->dropColumn('users', ['theme', 'language', 'notifications_enabled', 'is_active']);
    }
}
