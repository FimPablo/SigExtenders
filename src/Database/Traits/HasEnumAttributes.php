<?php

namespace FimPablo\SigExtenders\Database\Traits;

use FimPablo\SigExtenders\Utils\Arr;

trait HasEnumAttributes
{
    protected static function bootHasEnumAttributes(): void
    {
        static::retrieved(fn($model) => $model->appendEnums());
    }

    protected function appendEnums()
    {
        foreach ($this->enums ?? [] as $attrName => $enum) {
            $enumDescAttr = "{$attrName}DESC";
            $case = $enum::tryFrom($this->$attrName);

            $this->$enumDescAttr = [
                'id' => $this->$attrName,
                'name' => $case?->translate(),
                'case' => $case
            ];
        }
    }

    public function toArray()
    {
        $this->removeEnums();
        return parent::toArray();
    }

    protected function removeEnums()
    {
        foreach ($this->enums ?? [] as $attrName => $enum) {
            $enumDescAttr = "{$attrName}DESC";

            $this->$enumDescAttr = Arr::without($this->$enumDescAttr ?? [], 'case');
        }
    }
}
