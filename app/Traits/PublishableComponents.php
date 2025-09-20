<?php

declare(strict_types=1);

namespace Modules\Base\Traits;

use Illuminate\Support\Facades\Blade;

trait PublishableComponents
{
    abstract public function getModuleName(): string;

    abstract public function getModuleNameLower(): string;

    protected function publishableComponent($name, $class): void
    {
        Blade::component(class: $class, alias: $this->getModuleNameLower().'::'.$name);

        [$origin, $destination] = $this->originAndDestination($name);

        $this->publishes(
            [$origin => $destination],
            [
                'views',
                'publishable-components',
                $this->getModuleNameLower().'-publishable-components',
                $this->getModuleNameLower().'-component-'.$name,
            ]
        );
    }

    protected function originAndDestination($name): array
    {
        $component = str($name)->explode('.')->join('/');
        $path = "Resources/views/components/$component.blade.php";
        $moduleName = $this->getModuleName();
        $origin = module_path($moduleName, $path);
        $destination = resource_path("views/{$this->getModuleNameLower()}/components/$component.blade.php");

        return [$origin, $destination];
    }
}
