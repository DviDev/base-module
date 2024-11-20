<?php

namespace Modules\Base\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Base\Entities\Config\ConfigEntityModel;
use Modules\Base\Entities\Config\ConfigProps;
use Modules\Base\Factories\BaseFactory;

/**
 * @author Davi Menezes (davimenezes.dev@gmail.com)
 * @link https://github.com/DaviMenezes
 * @property-read  User $user
 * @method ConfigEntityModel toEntity()
 */
class ConfigModel extends BaseModel
{
    use HasFactory;
    use ConfigProps;

    public $timestamps = true;

    protected $casts = [
        'updated_at' => 'datetime'
    ];

    public function modelEntity(): string
    {
        return ConfigEntityModel::class;
    }

    protected static function newFactory(): BaseFactory
    {
        return new class extends BaseFactory {
            protected $model = ConfigModel::class;
        };
    }
    public static function table($alias = null): string
    {
        return self::dbTable('base_configs', $alias);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    static public function byValue($name)
    {
        return cache()->rememberForever("config::$name", function () {
            return ConfigModel::whereFn(fn(ConfigEntityModel $c) => [[$c->name, 'app_logo']])->first()->value;
        });
    }
}
