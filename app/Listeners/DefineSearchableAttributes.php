<?php

namespace Modules\Base\Listeners;

use Modules\Base\Entities\RecordType\RecordTypeEntityModel;
use Modules\Base\Models\RecordTypeModel;
use Modules\Project\Events\EntityAttributesCreatedEvent;
use Modules\Project\Models\ProjectModuleEntityAttributeModel;

class DefineSearchableAttributes
{
    private EntityAttributesCreatedEvent $event;

    public function handle(EntityAttributesCreatedEvent $event): void
    {
        $this->event = $event;
        if ($event->entity->module->name !== config('base.name')) {
            return;
        }

        foreach ($event->entity->entityAttributes as $attribute) {
            $this->default($attribute);
        }
    }

    protected function default(ProjectModuleEntityAttributeModel $attribute): void
    {
        if ($this->event->entity->name !== RecordTypeModel::table()) {
            return;
        }
        $p = RecordTypeEntityModel::props();
        if (in_array($attribute->name, [
            $p->id,
        ])) {
            $attribute->update(['searchable' => true]);
        }
    }
}
