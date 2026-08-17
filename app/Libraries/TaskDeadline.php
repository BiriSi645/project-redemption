<?php

namespace App\Libraries;

use DateTimeImmutable;
use DateTimeZone;

class TaskDeadline
{
    public static function fromDatabase(string $date, ?string $time, ?DateTimeZone $timezone = null): ?DateTimeImmutable
    {
        $timezone ??= new DateTimeZone('Europe/Istanbul');
        $value = $date . ' ' . ($time ?: '23:59:59');
        $deadline = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, $timezone);

        return $deadline ?: null;
    }
}
