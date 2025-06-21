<?php

namespace Modules\Base\Services\Errors;

use Modules\Base\Providers\BaseServiceProviderInterface;
use Nwidart\Modules\Module;

class ErrorMessages
{
    public static function getMessageDefault($code): string
    {
        $errors = collect([]);
        $fn = function (array $array) use ($errors) {
            collect($array)->map(fn (string $value, int|string $key) => $errors->put($key, $value));
        };
        $modules = \Module::allEnabled();
        /** @var Module $module */
        foreach ($modules as $module) {
            $module_name = $module->getName();
            $provider = 'Modules\\'.$module_name.'\Providers\\'.$module_name.'ServiceProvider';
            if (! in_array(BaseServiceProviderInterface::class, class_implements($provider))) {
                continue;
            }
            /** @var BaseServiceProviderInterface $provider */
            $error_type_class = $provider::errorTypeClass();
            $fn($error_type_class::errorMessages());
        }

        return $errors->get($code, 'Houve um erro ao processar a solicitação. Tente mais tarde');
    }
}
