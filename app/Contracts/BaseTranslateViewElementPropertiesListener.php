<?php

namespace Modules\Base\Contracts;

use Illuminate\Support\Stringable;
use Modules\View\Events\ElementPropertyCreatingEvent;
use Modules\View\Models\ElementPropertyModel;

abstract class BaseTranslateViewElementPropertiesListener
{
    public function handle(ElementPropertyCreatingEvent $event): void
    {
        $property = $event->property;

        if (! $this->validate($property)) {
            return;
        }

        $stringable = $this->getStringable($property);

        $property->value = $property->name === 'placeholder'
            ? $stringable->value()
            : $stringable->ucfirst()->value();
    }

    private function validate(ElementPropertyModel $property): bool
    {
        $property->load('element.structure.page.entity.module');
        $module = $property->element->structure->page->entity->module->name;
        if ($module !== $this->moduleName()) {
            return false;
        }
        if (! in_array($property->name, $this->propertiesToTranslate())) {
            return false;
        }
        if (in_array($property->value, ['id'])) {
            return false;
        }

        return true;
    }

    protected function propertiesToTranslate(): array
    {
        return ['placeholder', 'label'];
    }

    abstract protected function moduleName(): string;

    abstract protected function moduleNameLower(): string;

    protected function getStringable(ElementPropertyModel $property): Stringable
    {
        $str = $this->moduleNameLower();
        $entity = $property->element->structure->page->entity;
        $term = __("$str::$entity->name.$property->value");

        return str($term)->replace('_', ' ');
    }
}
