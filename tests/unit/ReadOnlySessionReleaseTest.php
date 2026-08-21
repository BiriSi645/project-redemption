<?php

use CodeIgniter\Test\CIUnitTestCase;

final class ReadOnlySessionReleaseTest extends CIUnitTestCase
{
    public function testPollingRoutesReleaseDatabaseSessionLockEarly(): void
    {
        $source = file_get_contents(APPPATH . 'Filters' . DIRECTORY_SEPARATOR . 'AuthFilter.php');

        $this->assertIsString($source);
        foreach ([
            'messages/preview',
            'notifications/preview',
            'system/active-users',
            'system/heartbeat',
            'system/live-updates',
            'system/realtime-token',
        ] as $path) {
            $this->assertStringContainsString("'{$path}'", $source);
        }
        $this->assertStringContainsString('session()->close()', $source);
    }

    public function testHealthRoutesDoNotInitializeCsrfSession(): void
    {
        $source = file_get_contents(APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Filters.php');

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "'csrf' => ['except' => ['system/ping', 'system/version']]",
            $source,
        );
    }
}
