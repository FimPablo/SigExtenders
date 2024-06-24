<?php

namespace FimPablo\SigExtenders\Database\External;

use FimPablo\SigExtenders\Database\Model;
use Illuminate\Support\Collection;

class ExternalModel extends Model
{
    protected $modelSocket;

    public function __construct(array|Collection $attributes = new Collection())
    {

        $this->fillableFromArray(array_keys($attributes instanceof Collection ? $attributes->toArray() : $attributes));
        $this->attributes = $attributes instanceof Collection ? $attributes->toArray() : $attributes;

        $this->prefixColumns = $this->prefixColumns === '' ? $this->guessPrefix() : $this->prefixColumns;
        parent::__construct();
    }

    public function newEloquentBuilder($query)
    {
        return new ExternalBuilder($query);
    }

    public function getSocket()
    {
        return $this->modelSocket;
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function guessPrefix()
    {
        $strings = array_keys($this->attributes);

        if (empty($strings)) {
            return "";
        }

        $ocorrencias = collect($strings)
            ->flatMap(function ($string) {
                return array_map(function ($index) use ($string) {
                    return substr($string, 0, $index);
                }, range(1, strlen($string)));
            })
            ->countBy();

        return $ocorrencias
            ->filter(fn($value) => $value == $ocorrencias->max())
            ->keys()
            ->sortByDesc(function ($item) {
                return strlen($item);
            })->first();
    }

    public function getAttribute($key)
    {
        if (array_key_exists($this->prefixColumns . $key, $this->attributes)) {
            return $this->attributes[$this->prefixColumns . $key];
        }

        return $this->attributes[$key];
    }

    public function setAttribute($key, $value)
    {
        if (array_key_exists($this->prefixColumns . $key, $this->attributes)) {
            return $this->attributes[$this->prefixColumns . $key] = $value;
        }

        return $this->attributes[$key] = $value;
    }
}
