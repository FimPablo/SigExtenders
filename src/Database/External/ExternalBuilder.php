<?php

namespace FimPablo\SigExtenders\Database\External;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;

class ExternalBuilder extends Builder
{
    public function getModels($columns = ['*'])
    {
        $runnableService = app($this->getModel()->getSocket());

        return $this->model->hydrate(
            $runnableService
                ->search(
                    class_basename($this->getModel()),
                    $this->query->wheres,
                    array_keys($this->getEagerLoads())
                )->all($columns)
        )->all();
    }

    public function getRelation($name)
    {
        $relation = Relation::noConstraints(function () use ($name) {
            try {
                return new HasOne($this, $this->getModel(), $this->model->getKeyName(), $this->model->getKeyName());
            } catch (BadMethodCallException) {
                throw RelationNotFoundException::make($this->getModel(), $name);
            }
        });

        $nested = $this->relationsNestedUnder($name);

        if (count($nested) > 0) {
            $relation->getQuery()->with($nested);
        }

        return new HasOne($this, $this->getModel(), $this->model->getKeyName(), $this->model->getKeyName());
    }

    public function get($columns = ['*'])
    {
        $builder = $this->applyScopes();

        if (count($models = $builder->getModels($columns)) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        return $builder->getModel()->newCollection($models);
    }

    public function eagerLoadRelations(array $models)
    {
        foreach ($this->eagerLoad as $name => $constraints) {
            if (!str_contains($name, '.')) {
                foreach ($models as $model) {
                    if ($model->{$name}) {
                        $model->{$name} = new ExternalModel($model->{$name});
                    }
                }
            }
        }

        return $models;
    }
}
