<?php

declare(strict_types=1);

$vercelConfig = [
    'app.baseURL' => 'https://project-redemption.vercel.app/',
    'app.indexPage' => '',
];

foreach ($vercelConfig as $key => $value) {
    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

$writablePath = sys_get_temp_dir() . '/project-redemption-writable';

foreach (['cache', 'debugbar', 'logs', 'session', 'uploads'] as $directory) {
    $path = $writablePath . '/' . $directory;

    if (! is_dir($path)) {
        mkdir($path, 0775, true);
    }
}

require dirname(__DIR__) . '/public/index.php';
