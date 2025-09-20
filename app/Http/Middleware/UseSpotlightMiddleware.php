<?php

declare(strict_types=1);

namespace Modules\Base\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Livewire;
use Modules\Base\Events\UsingSpotlightEvent;

final class UseSpotlightMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('base.use.spotlight')) {
            return $next($request);
        }

        $uri = $request->getRequestUri();
        if (str($uri)->startsWith('/_debugbar')) {
            return $next($request);
        }

        if (str($uri)->contains('/livewire')) {
            $string = url()->previous();
            $uri = str($string)->remove(config('app.url'))->value();
        }

        event(new UsingSpotlightEvent($uri));

        $response = $next($request);

        if (! ($response instanceof Response)) {
            return $response;
        }

        $spotlight = Livewire::mount('livewire-ui-spotlight');

        $content = str($response->getContent())
            ->remove('<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>')
            ->replace('</body>', $spotlight.'</body>')
            ->value();

        return $response->setContent($content);
    }
}
