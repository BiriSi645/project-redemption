<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RedactPasswordResetAuditPaths extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('audit_logs')) return;

        $this->db->table('audit_logs')
            ->groupStart()
                ->like('path', 'reset-password/', 'after')
                ->orLike('path', 'index.php/reset-password/', 'after')
            ->groupEnd()
            ->update(['path' => 'reset-password/[REDACTED]']);
    }

    public function down()
    {
        // Redacted credentials must never be reconstructed.
    }
}
