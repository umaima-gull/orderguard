<?php

namespace App\Http\Middleware;

use App\Support\Permissions;
use Closure;
use Illuminate\Http\Request;

class RequireRole
{
    public function handle(Request $request, Closure $next, string $level)
    {
        abort_unless(Permissions::can($level), 403, 'You do not have permission to do this.');

        return $next($request);
    }
}