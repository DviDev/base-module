<?php

namespace Modules\Base\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * @extends Model
 */
trait HasFactoryFirstOrCreate
{
    /**
     * Find the first record matching the attributes or create it using the factory.
     *
     * @return static
     */
    public static function firstOrCreateViaFactory(array $attributes, array $values = [])
    {
        if (app()->isProduction()) {
            throw new \Exception(__('This method is not allowed in production'));
        }

        if (! is_null($instance = static::where($attributes)->first())) {
            return $instance;
        }

        return static::factory()->create(array_merge($attributes, $values));
    }
}
