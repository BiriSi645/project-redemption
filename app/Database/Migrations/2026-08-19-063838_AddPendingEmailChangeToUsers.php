<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPendingEmailChangeToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'pending_email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'email_verification_sent_at',
            ],

            'pending_email_verification_token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'pending_email',
            ],

            'pending_email_verification_expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'pending_email_verification_token',
            ],

            'pending_email_verification_attempts' => [
                'type' => 'TINYINT',
                'constraint' => 3,
                'unsigned' => true,
                'default' => 0,
                'after' => 'pending_email_verification_expires_at',
            ],

            'pending_email_verification_sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'pending_email_verification_attempts',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', [
            'pending_email',
            'pending_email_verification_token',
            'pending_email_verification_expires_at',
            'pending_email_verification_attempts',
            'pending_email_verification_sent_at',
        ]);
    }
}