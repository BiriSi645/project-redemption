<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DefaultThemeToSystem extends Migration
{
    public function up()
    {
        $this->db->query("UPDATE users SET theme = 'system' WHERE theme = 'light'");
        $this->db->query("ALTER TABLE users MODIFY theme VARCHAR(20) NOT NULL DEFAULT 'system'");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE users MODIFY theme VARCHAR(20) NOT NULL DEFAULT 'light'");
    }
}
