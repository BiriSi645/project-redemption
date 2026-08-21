<?php

namespace App\Libraries;

final class PasswordPolicy
{
    public const MIN_LENGTH = 10;

    public static function accepts(string $password): bool
    {
        return mb_strlen($password, 'UTF-8') >= self::MIN_LENGTH;
    }

    public static function minimumLengthMessage(): string
    {
        return 'Şifre en az ' . self::MIN_LENGTH . ' karakter olmalıdır.';
    }
}
