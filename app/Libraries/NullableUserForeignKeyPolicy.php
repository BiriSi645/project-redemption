<?php

namespace App\Libraries;

class NullableUserForeignKeyPolicy
{
    public const ON_UPDATE = 'CASCADE';
    public const ON_DELETE = 'SET NULL';
}
