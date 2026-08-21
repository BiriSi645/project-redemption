<?php

use CodeIgniter\Session\Handlers\DatabaseHandler;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Session;

final class DatabaseSessionMigrationTest extends CIUnitTestCase
{
    public function testSessionConfigMatchesMigratedTable(): void
    {
        $config = new Session();
        $migration = file_get_contents(
            APPPATH . 'Database' . DIRECTORY_SEPARATOR . 'Migrations' . DIRECTORY_SEPARATOR
            . '2026-08-21-120000_CreateCiSessionsTable.php'
        );

        $this->assertSame(DatabaseHandler::class, $config->driver);
        $this->assertSame('ci_sessions', $config->savePath);
        $this->assertFalse($config->matchIP);
        $this->assertStringContainsString("createTable('ci_sessions', true)", $migration);
        $this->assertStringContainsString("addKey('id', true)", $migration);
        $this->assertStringContainsString("addKey('timestamp')", $migration);
        $this->assertStringContainsString("'data' => ['type' => 'BLOB'", $migration);
    }
}
