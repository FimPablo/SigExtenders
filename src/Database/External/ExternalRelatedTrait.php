<?php

namespace FimPablo\SigExtenders\Database\External;

trait ExternalRelatedTrait
{
    protected function hasOneExternal($related, $foreignKey = null, $localKey = null)
    {
        [$instance, $foreignKey, $localKey] = $this->prepereRelations($related, $foreignKey, $localKey);
        return new HasOne($instance->newQuery(), $this, $foreignKey, $localKey);
    }

    protected function hasManyExternal($related, $foreignKey = null, $localKey = null)
    {
        [$instance, $foreignKey, $localKey] = $this->prepereRelations($related, $foreignKey, $localKey);
        return new HasMany($instance->newQuery(), $this, $foreignKey, $localKey);
    }

    private function prepereRelations($related, $foreignKey = null, $localKey = null)
    {
        return [
            $this->newRelatedInstance($related),
            $foreignKey ?? $this->getForeignKey(),
            $localKey ?? $this->getKeyName(),
        ];
    }

    public function newEloquentBuilder($query)
    {
        return new QueryBuilder($query);
    }

}
