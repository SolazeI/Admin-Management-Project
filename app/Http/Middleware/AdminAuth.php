<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->get('is_admin') === true) {
            return $next($request);
        }

        return redirect()->route('admin.login');
    }
}

