<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('logged_in') || ! session()->get('user_id')) {
            return redirect()
                ->to(site_url('login'))
                ->with('errors', ['auth' => 'Bu sayfayı görüntülemek için giriş yapmalısınız.']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Bu filtre yalnızca istek öncesi oturum kontrolü yapar.
    }
}
