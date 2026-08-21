<?php

use CodeIgniter\Test\CIUnitTestCase;

final class ProductionCodeVersionTest extends CIUnitTestCase
{
    public function testVercelInjectsDeployIdentifierAsAppVersion(): void
    {
        $source = file_get_contents(ROOTPATH . 'api' . DIRECTORY_SEPARATOR . 'index.php');

        $this->assertStringContainsString("getenv('APP_VERSION')", $source);
        $this->assertStringContainsString("getenv('VERCEL_GIT_COMMIT_SHA')", $source);
        $this->assertStringContainsString("'app.version' => \$releaseVersion", $source);
    }

    public function testProductionFallbackOccursBeforeFilesystemTraversal(): void
    {
        $source = file_get_contents(APPPATH . 'Libraries' . DIRECTORY_SEPARATOR . 'CodeVersion.php');
        $guard = strpos($source, "ENVIRONMENT === 'production'");
        $traversal = strpos($source, 'new RecursiveIteratorIterator');

        $this->assertNotFalse($guard);
        $this->assertNotFalse($traversal);
        $this->assertLessThan($traversal, $guard);
    }
}
