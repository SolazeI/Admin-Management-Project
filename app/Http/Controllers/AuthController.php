<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function authenticate(Request $request)
    {
        $password = $request->input('password');

        // Simple password check example
        if ($password === 'admin123') {
            return redirect('/admin');
        } else {
            return back()->with('error', 'Incorrect password');
        }
    }
}
