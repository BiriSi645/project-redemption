<?php

use App\Libraries\NullableUserForeignKeyPolicy;
use CodeIgniter\Test\CIUnitTestCase;

final class ForeignKeyPolicyTest extends CIUnitTestCase
{
    public function testNullableUserReferencesSurviveUserDeletion(): void
    {
        $this->assertSame('CASCADE', NullableUserForeignKeyPolicy::ON_UPDATE);
        $this->assertSame('SET NULL', NullableUserForeignKeyPolicy::ON_DELETE);
    }
}
