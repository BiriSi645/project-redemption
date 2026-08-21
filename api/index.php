<?php

declare(strict_types=1);

$configuredBaseUrl = trim((string) (getenv('APP_BASE_URL') ?: ''));
$vercelDeploymentUrl = trim((string) (getenv('VERCEL_URL') ?: ''));
$releaseVersion = trim((string) (
    getenv('APP_VERSION')
    ?: getenv('VERCEL_GIT_COMMIT_SHA')
    ?: $vercelDeploymentUrl
));
$baseUrl = $configuredBaseUrl !== '' ? $configuredBaseUrl : $vercelDeploymentUrl;

if ($baseUrl !== '' && ! str_contains($baseUrl, '://')) {
    $baseUrl = 'https://' . $baseUrl;
}

$parts = $baseUrl === '' ? false : parse_url($baseUrl);
if (
    filter_var($baseUrl, FILTER_VALIDATE_URL) === false
    ||
    ! is_array($parts)
    || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
    || empty($parts['host'])
    || isset($parts['user'])
    || isset($parts['pass'])
    || isset($parts['query'])
    || isset($parts['fragment'])
) {
    throw new RuntimeException('APP_BASE_URL veya VERCEL_URL geçerli bir HTTPS adresi olmalıdır.');
}

$baseUrl = 'https://' . $parts['host']
    . (isset($parts['port']) ? ':' . $parts['port'] : '')
    . rtrim((string) ($parts['path'] ?? ''), '/') . '/';

$vercelConfig = [
    'app.baseURL' => $baseUrl,
    'app.indexPage' => '',
    'app.version' => $releaseVersion,
];

foreach ($vercelConfig as $key => $value) {
    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// Vercel terminates TLS before forwarding the request to the PHP runtime.
// CodeIgniter otherwise sees that internal hop as HTTP and refuses to send
// production Secure cookies, turning every authenticated response into a 500.
if (getenv('VERCEL')) {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = '443';
}

$writablePath = sys_get_temp_dir() . '/project-redemption-writable';

foreach (['cache', 'debugbar', 'logs', 'session', 'uploads'] as $directory) {
    $path = $writablePath . '/' . $directory;

    if (! is_dir($path)) {
        mkdir($path, 0775, true);
    }
}

require dirname(__DIR__) . '/public/index.php';
