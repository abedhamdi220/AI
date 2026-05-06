<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{

    public function handle(Request $request, Closure $next): Response
    {

        if (auth('api')->check() && auth('api')->user()->is_admin) {
            return $next($request);
        }
        return api_error('غير مصرح لك بالوصول. هذه الصلاحية مخصصة لمديري النظام فقط.', 403);
    }
}
