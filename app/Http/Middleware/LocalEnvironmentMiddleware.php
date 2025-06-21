<?php

namespace Modules\Base\Http\Middleware;

use Closure;
use http\Client\Response;
use Illuminate\Http\Request;

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
