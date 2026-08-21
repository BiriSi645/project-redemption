<?php

namespace App\Controllers;

use App\Libraries\CodeVersion;

class UpdateStatus extends BaseController
{
    public function version()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        return $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->setJSON(['version' => (new CodeVersion())->current()]);
    }
}
