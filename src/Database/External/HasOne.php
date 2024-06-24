<?php

namespace FimPablo\SigExtenders\Database\External;

use Illuminate\Database\Eloquent\Collection;

class HasOne extends \Illuminate\Database\Eloquent\Relations\HasOne
{
    protected function buildDictionary(Collection $results)
    {
        $foreign = $this->getForeignKeyName();

        return $results->mapToDictionary(function ($result) use ($foreign) {
            return [$this->getDictionaryKey($result->toArray()[$foreign]) => $result];
        })->all();
    }

}
