<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }

    public function entrada(): string{
        return view('home');
    }

    public function documentation(): string{
        return view('documentation');
    }
    
}
