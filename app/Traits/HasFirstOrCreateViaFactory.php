<?php

namespace Modules\Base\Traits;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * @extends Model
 */
trait HasFirstOrCreateViaFactory
{
    /**
     * Find the first record matching the attributes or create it using the factory.
     *
     * @throws Exception
     */
    public static function firstOrCreateViaFactory(array $attributes, array $values = []): static
    {
        if (app()->isProduction()) {
            throw new Exception(__('This method is not allowed in production'));
        }

        /** @var Builder $query */
        $query = static::query();
        foreach ($attributes as $key => $value) {
            $query->where($key, '=', $value);
        }
        if (! is_null($instance = $query->first())) {
            return $instance;
        }

        try {
            return static::factory()->create(array_merge($attributes, $values));
        } catch (Exception $e) {
            if (config('app.env') == 'local') {
                Log::info(__('Failed to create the model: ').$e->getMessage());
                Log::info($query->toRawSql());
            }
            throw $e;
        }
    }
}
