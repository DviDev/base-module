<?php

namespace Base\Providers;

use Illuminate\Support\Facades\Blade;

trait PublishableComponents
{
    protected function publishableComponent($name, $class): void
    {
        Blade::component(class: $class, alias: $this->getModuleNameLower() . '::' . $name);

        [$origin, $destination] = $this->originAndDestination($name);

        $this->publishes(
            [$origin => $destination],
            [
                'views',
                'publishable-components',
                $this->getModuleNameLower() . '-publishable-components',
                $this->getModuleNameLower() . '-component-' . $name,
            ]
        );
    }
}
