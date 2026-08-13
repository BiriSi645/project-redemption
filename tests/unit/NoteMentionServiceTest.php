<?php

namespace Tests\Unit;

use App\Libraries\NoteMentionService;
use CodeIgniter\Test\CIUnitTestCase;

class NoteMentionServiceTest extends CIUnitTestCase
{
    public function testExtractsUniqueUnicodeUsernames(): void
    {
        $usernames = (new NoteMentionService())->usernames('Merhaba @ceyda ve @denemekullanıcı. Tekrar: @ceyda');

        $this->assertSame(['ceyda', 'denemekullanıcı'], $usernames);
    }

    public function testIgnoresEmailAddressesAndTooShortNames(): void
    {
        $usernames = (new NoteMentionService())->usernames('mail@example.com @ab ama @user_name geçerli');

        $this->assertSame(['user_name'], $usernames);
    }
}
