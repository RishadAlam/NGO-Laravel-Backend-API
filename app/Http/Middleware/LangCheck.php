<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class LangCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->header('Accept-Language'), ['en', 'bn'])) {
            App::setLocale($request->header('Accept-Language'));
            return $next($request);
        }
        return response(
            [
                'success'   => false,
                "message"   => "Unknown Language!"
            ],
            400
        );
    }
}
