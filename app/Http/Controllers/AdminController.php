<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        // Load your admin view (example: admin.blade.php)
        return view('admin');
    }
}
