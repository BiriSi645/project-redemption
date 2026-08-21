<?php

use CodeIgniter\Test\CIUnitTestCase;

final class ProductionCookieSecurityTest extends CIUnitTestCase
{
    public function testCookieSecureFlagDependsOnProductionEnvironment(): void
    {
        $source = file_get_contents(APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Cookie.php');

        $this->assertStringContainsString("public bool \$secure = ENVIRONMENT === 'production';", $source);
    }

    public function testVercelForcesCodeIgniterProductionEnvironment(): void
    {
        $config = json_decode(file_get_contents(ROOTPATH . 'vercel.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('production', $config['env']['CI_ENVIRONMENT'] ?? null);
    }
}
