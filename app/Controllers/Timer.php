<?php

namespace App\Controllers;

class Timer extends BaseController
{
    public function index(): string
    {
        return view('timer/index', [
            'title' => 'Kronometre',
        ]);
    }
}
