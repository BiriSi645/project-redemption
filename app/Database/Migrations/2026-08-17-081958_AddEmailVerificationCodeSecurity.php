<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailVerificationCodeSecurity extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'email_verification_attempts' => [
                'type' => 'TINYINT',
                'constraint' => 3,
                'unsigned' => true,
                'default' => 0,
                'after' => 'email_verification_expires_at',
            ],

            'email_verification_sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'email_verification_attempts',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', [
            'email_verification_attempts',
            'email_verification_sent_at',
        ]);
    }
}