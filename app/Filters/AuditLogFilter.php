<?php

namespace App\Filters;

use App\Libraries\AuditLogger;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuditLogFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (strtoupper($request->getMethod()) !== 'POST') {
            return;
        }

        $path = trim($request->getUri()->getPath(), '/');
        $path = preg_replace('#^index\.php/?#', '', $path) ?? $path;
        $path = AuditLogger::sanitizePath($path);
        if (in_array($path, ['login', 'register', 'logout', 'profile/delete'], true)) {
            return;
        }
        if ($path === 'games/score' || preg_match('#^games/room/[a-zA-Z0-9]+/move$#', $path) || preg_match('#^messages/\d+/send$#', $path)) {
            return;
        }

        [$action, $description] = AuditLogger::describePath($path);
        AuditLogger::record(
            session()->get('user_id') ? (int) session()->get('user_id') : null,
            $action,
            $description,
            $request->getMethod(),
            $path,
            $response->getStatusCode()
        );
    }
}
