<?php

namespace Modules\Base\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\App\Entities\User\UserEntityModel;
use Modules\App\Entities\User\UserProps;
use Modules\App\Models\UserTypeModel;
use Modules\Base\Contracts\BaseModelImplementation;
use Modules\Base\Contracts\BaseModelInterface;
use Modules\Base\Contracts\HasFactory;

/**
 * @method $this find($id)
 * @property-read UserTypeModel $type
 */
abstract class BaseUser extends Authenticatable implements BaseModelInterface
{
    use HasFactory;
    use Notifiable;
    use BaseModelImplementation;
    use UserProps;
    use HasUuids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function modelEntity(): string
    {
        return UserEntityModel::class;
    }

    public static function table($alias = null): string
    {
        return self::dbTable('users', $alias);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(UserTypeModel::class, 'type_id');
    }

    public function isAdmin(): bool
    {
        return $this->type_id == 3;
    }

    public function isSuperAdmin(): bool
    {
        return $this->type_id == 2;
    }

    public function isCustomer(): bool
    {
        return $this->type_id == 4;
    }

    public function isDeveloper(): bool
    {
        return $this->type_id == 1;
    }

    public function isGuest(): bool
    {
        return $this->type_id == 5;
    }
}
