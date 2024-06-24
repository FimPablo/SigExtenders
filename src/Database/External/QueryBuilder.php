<?php

namespace FimPablo\SigExtenders\Database\External;

use FimPablo\SigExtenders\Database\Repository;
use Illuminate\Database\Eloquent\Builder;

class QueryBuilder extends Builder
{
    public function __construct($query)
    {
        parent::__construct($query);
    }

    public function whereHas($relation, \Closure $callback = null, $operator = '>=', $count = 1)
    {
        $model = $this->getModel();
        $relationFunction = $model->{$relation}();
        $relatedModel = $relationFunction->getModel();

        if (!is_subclass_of($relatedModel, ExternalModel::class)) {
            return parent::whereHas($relation, $callback, $operator, $count);
        }

        $match = (new Repository($relatedModel::class))
            ->findAll(
                $callback(
                    $relatedModel->newQuery()
                )
            )->pluck(
                $relationFunction->getForeignKeyName()
            );

        $this->whereIn(
            $relationFunction->getLocalKeyName(),
            $match
        );
        return $this;
    }
}
