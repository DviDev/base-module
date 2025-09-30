<?php

namespace Modules\Base\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Base\Contracts\BaseModel;
use Modules\Base\Models\RecordModel;
use Modules\Permission\Enums\Actions;

/**
 * @extends BaseModel
 *
 * @property RecordModel $record
 */
trait HasRecord
{
    public function record(): BelongsTo
    {
        return $this->belongsTo(RecordModel::class, 'record_id');
    }

    public function can(Actions $action): ?bool
    {
        return $this->record->can($action);
    }

    public function actions(): HasMany
    {
        return $this->record->actions();
    }
}
