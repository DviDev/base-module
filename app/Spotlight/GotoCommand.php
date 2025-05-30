<?php

namespace Modules\Base\Spotlight;

use Illuminate\Routing\Route;
use LivewireUI\Spotlight\Spotlight;
use LivewireUI\Spotlight\SpotlightCommand;
use LivewireUI\Spotlight\SpotlightCommandDependencies;
use LivewireUI\Spotlight\SpotlightCommandDependency;
use LivewireUI\Spotlight\SpotlightSearchResult;

class GotoCommand extends SpotlightCommand
{
    protected string $name = 'goto';
    protected string $description = 'Go to routes';
    protected array $synonyms = [
        'page',
        'route'
    ];

    public function dependencies(): ?SpotlightCommandDependencies
    {
        return SpotlightCommandDependencies::collection()
            ->add(
                SpotlightCommandDependency::make('route')
                    ->setPlaceholder('Enter the route')
            );
    }

    public function searchRoute($query)
    {
        //Obs. Filters by str(), Str::, it doesn't work.
        // Using collect([])->filter(), or collect([])->expect() etc. won't work for now
        $routes = \Illuminate\Support\Facades\Route::getRoutes();

        $collection = collect($routes)->all();
        $array = [];
        /**@var Route $route */
        foreach ($collection as $route) {
            if ($route->hasParameters()) {
                continue;
            }
            if (str_contains($route->uri(), 'livewire')) {
                continue;
            }
            if (str_contains($route->uri(), '_debugbar')) {
                continue;
            }
            if (str_contains($route->uri(), 'wire-spy')) {
                continue;
            }
            if (str_contains($route->uri(), 'sanctum')) {
                continue;
            }
            if (str_contains($route->uri(), '_ignition')) {
                continue;
            }
            if (str_contains($route->uri(), '{fallbackPlaceholder}')) {
                continue;
            }
            if (!str_contains($route->getName() ?: $route->uri(), $query)) {
                continue;
            }
            $array[] = $route;
        }
        return collect($array)->map(function (Route $route) {
            $name = $route->getName() ?: $route->uri();
            return new SpotlightSearchResult(
                '/' . $route->uri(),
                $route->uri(),
                sprintf('Go to route %s', $name)
            );
        });
    }

    public function execute(Spotlight $spotlight, $route)
    {
        $spotlight->redirect($route);
    }
}
