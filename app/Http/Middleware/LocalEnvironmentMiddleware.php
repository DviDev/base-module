<?php

namespace Modules\Base\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LocalEnvironmentMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->isLocal()) {
            abort(403, 'Acesso negado');
        }

        return $next($request);
    }
}
