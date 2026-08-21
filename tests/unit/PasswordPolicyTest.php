<?php

namespace Tests\Unit;

use App\Libraries\PasswordPolicy;
use CodeIgniter\Test\CIUnitTestCase;

final class PasswordPolicyTest extends CIUnitTestCase
{
    public function testRejectsPasswordShorterThanTenCharacters(): void
    {
        $this->assertSame(10, PasswordPolicy::MIN_LENGTH);
        $this->assertFalse(PasswordPolicy::accepts('123456789'));
    }

    public function testAcceptsPasswordAtMinimumLength(): void
    {
        $this->assertTrue(PasswordPolicy::accepts('1234567890'));
    }

    public function testCountsUnicodeCharactersInsteadOfBytes(): void
    {
        $this->assertFalse(PasswordPolicy::accepts('şifre1234'));
        $this->assertTrue(PasswordPolicy::accepts('şifre12345'));
    }
}
