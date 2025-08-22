<?php

namespace Modules\Base\Contracts;

use Modules\View\Events\ElementPropertyCreatedEvent;
use Modules\View\Models\ElementPropertyModel;

abstract class BaseTranslateViewElementPropertiesListener
{
    public function handle(ElementPropertyCreatedEvent $event): void
    {
        $property = $event->property;

        if (!$this->validate($property)) {
            return;
        }

        $entity = $property->element->structure->page->entity;
        $str = $this->moduleNameLower();
        $stringable = str(__("{$str}::{$entity->name}.{$property->value}"))->replace('_', ' ');

        if ($property->name === 'placeholder') {
            $property->value = $stringable->value();
            return;
        }
        $property->value = $stringable->ucfirst()->value();
    }

    protected function propertiesToTranslate(): array
    {
        return ['placeholder', 'label'];
    }

    private function validate(ElementPropertyModel $property): bool
    {
        $property->load('element.structure.page.entity.module');
        $module = $property->element->structure->page->entity->module->name;
        if ($module !== $this->moduleName()) {
            return false;
        }
        if (!in_array($property->name, $this->propertiesToTranslate())) {
            return false;
        }
        if (in_array($property->value, ['id'])) {
            return false;
        }
        return true;
    }

    abstract protected function moduleName(): string;

    abstract protected function moduleNameLower(): string;
}
