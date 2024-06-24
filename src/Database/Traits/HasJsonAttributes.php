<?php

namespace FimPablo\SigExtenders\Database\Traits;
use FimPablo\SigExtenders\Database\Traits\Src\ObjectAtrribute;


trait HasJsonAttributes
{
    protected static function bootHasJsonAttributes(): void
    {
        static::creating(fn($model) => $model->castJsonsAttrsToString());
        static::retrieved(fn($model) => $model->castJsonsAttrsToDataStructure());
        static::updating(fn($model) => $model->castJsonsAttrsToString());
        static::updated(fn($model) => $model->castJsonsAttrsToDataStructure());
    }

    public function getJsonAtrributes()
    {
        return $this->jsonAttr;
    }

    protected function castJsonsAttrsToString()
    {
        foreach ($this->jsonAttr ?? [] as $attr) {
            $this->$attr = json_encode($this->$attr) === null ? $this->$attr : json_encode($this->$attr);
        }
    }

    protected function castJsonsAttrsToDataStructure()
    {
        foreach ($this->jsonAttr ?? [] as $attr) {
            if ($this->{$attr} === null) {
                continue;
            }

            if ($this->{$attr} == 'null') {
                $this->{$attr} = null;
                continue;
            }

            $decodetry = json_decode($this->{$attr});

            if ($decodetry === null) {
                continue;
            }

            $this->{$attr} = $decodetry;

            if (is_object($this->{$attr})) {
                $this->{$attr} = new ObjectAtrribute($this->{$attr});
            }
        }
    }
}
