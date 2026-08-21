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

    public function testVercelTlsTerminationIsReportedToCodeIgniter(): void
    {
        $source = file_get_contents(ROOTPATH . 'api' . DIRECTORY_SEPARATOR . 'index.php');

        $this->assertIsString($source);
        $this->assertStringContainsString("if (getenv('VERCEL'))", $source);
        $this->assertStringContainsString("\$_SERVER['HTTPS'] = 'on';", $source);
        $this->assertStringContainsString("\$_SERVER['SERVER_PORT'] = '443';", $source);
    }
}
