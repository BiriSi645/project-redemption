<?php

use CodeIgniter\Test\CIUnitTestCase;

final class VercelBaseUrlSecurityTest extends CIUnitTestCase
{
    public function testVercelEntryPointUsesEnvironmentInsteadOfHardCodedDomain(): void
    {
        $source = file_get_contents(ROOTPATH . 'api' . DIRECTORY_SEPARATOR . 'index.php');

        $this->assertStringContainsString("getenv('APP_BASE_URL')", $source);
        $this->assertStringContainsString("getenv('VERCEL_ENV')", $source);
        $this->assertStringContainsString("getenv('VERCEL_PROJECT_PRODUCTION_URL')", $source);
        $this->assertStringContainsString("getenv('VERCEL_URL')", $source);
        $this->assertStringContainsString("\$vercelEnvironment === 'production'", $source);
        $this->assertStringNotContainsString('project-redemption.vercel.app', $source);
        $this->assertStringContainsString("!== 'https'", $source);
        $this->assertStringContainsString("isset(\$parts['query'])", $source);
        $this->assertStringContainsString("isset(\$parts['fragment'])", $source);
    }
}
