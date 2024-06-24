<?php

namespace FimPablo\SigExtenders\Database;

use FimPablo\SigExtenders\Database\External\ExternalBuilder;
use FimPablo\SigExtenders\Database\Model;
use FimPablo\SigExtenders\Database\Traits\HasJsonAttributes;
use FimPablo\SigExtenders\Utils\Arr;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class Repository
{
    protected $relatedModel;
    public function __construct($model = null)
    {
        if ($model) {
            $this->relatedModel = $model;
        }
    }

    public function find(array $modelInfo, array $relations = [], bool $canRetunDeleted = false, string $orderBy = null, array $having = [])
    {
        return $this->buildFindQuery($modelInfo, $relations, $canRetunDeleted, $orderBy, $having)->first();
    }

    public function exists(array $modelInfo, array $relations = [], bool $canRetunDeleted = false, array $having = [])
    {
        $query = $this->buildFindQuery($modelInfo, $relations, $canRetunDeleted, null, $having);
        return $query->exists();
    }

    public function findAll(array $modelInfo, array $relations = [], bool $canRetunDeleted = false, string $orderBy = null, array $having = []): Collection
    {
        return $this->buildFindQuery($modelInfo, $relations, $canRetunDeleted, $orderBy, $having)->get();
    }

    public function findAllChunked(array $modelInfo, callable $processChunk, int $chunkSize, array $relations = [], bool $canRetunDeleted = false, string $orderBy = null, array $having = []): bool
    {
        $query = $this->buildFindQuery($modelInfo, $relations, $canRetunDeleted, $orderBy, $having);
        return $query->chunk($chunkSize, $processChunk);
    }

    public function listAll(array $modelInfo, array $relations = [], bool $paginate = true, bool $canRetunDeleted = false, string $orderBy = null, array $having = []): Collection|LengthAwarePaginator
    {
        $query = $this->buildFindQuery($modelInfo, $relations, $canRetunDeleted, $orderBy, $having);

        if ($paginate)
            return $query->paginate(Arr::get(request()->all(), 'perPage', 100));

        return $query->get();
    }

    public function create(array $modelInfo, array|bool $validate = ['*'])
    {
        if ($validate && $this->exists(Arr::just($modelInfo, $validate)))
            throw new \DomainException('Já existe um cadastro com essas informações', 400);

        return $this->relatedModel::create($modelInfo);
    }

    public function createIfNotExists(array $modelInfo)
    {
        $modelFinded = $this->find($modelInfo);

        if ($modelFinded)
            return $modelFinded;

        return $this->relatedModel::create($modelInfo);
    }

    public function getById($id, array $relations = [], bool $canRetunDeleted = false)
    {
        $query = $this->relatedModel::query();
        $query->where([
            [$query->getModel()->getKeyName(), $id],
        ]);

        if (!$canRetunDeleted)
            $query->whereNotDeleted();

        $query->with($relations);

        return $query->first();
    }

    public function update(&$model, array $data, bool $filterNull = true): bool
    {
        $this->filterModel($data, $filterNull);

        $sts = $model->update($data);
        $key = $model->getKeyName();
        $model = $this->getById($model->$key);
        return $sts;
    }

    public function updateUnique(&$model, array $data, array $withUnique = [], bool $filterNull = true): bool
    {
        $this->filterModel($data, $filterNull);
        $key = $model->getKeyName();

        $finded = $this->find(
            modelInfo: Arr::just($data, $withUnique)
        );

        if ($finded && ($finded?->{$key} != $model->{$key}))
            throw new \DomainException('Já existe um cadastro com essas informações', 400);

        $sts = $model->update($data);
        $model = $this->getById($model->$key);
        return $sts;
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    protected function buildQuery(bool $canRetunDeleted, Builder &$query, array $modelInfo, string $orderBy = null, bool $filterNull = true, $having = [])
    {
        if (!$canRetunDeleted)
            $query->whereNotDeleted();

        $hases = $this->evaluateRelationsInSearch($modelInfo);
        $hases = array_merge($hases, $having);

        if (!($query instanceof ExternalBuilder))
            $this->filterModel($modelInfo, $filterNull);


        Arr::each($modelInfo, fn($info, $key) => $this->buildWhereClause($query, $key, $info));
        Arr::each($hases, fn($info, $key) => $this->buildWhereHasClause($query, $key, $info));

        if ($orderBy)
            $query->orderBy($orderBy);

        return $modelInfo;
    }

    private function evaluateRelationsInSearch(array $modelInfo)
    {

        $evaluatedArray = [];

        Arr::each(
            Arr::where(
                $modelInfo,
                function ($value, $key) {
                    if (!str_contains($key, '.') || $value === null)
                        return false;

                    return method_exists($this->relatedModel, explode('.', $key)[0]);
                }
            ) ?? [],
            function ($value, $key) use (&$evaluatedArray) {
                [$index, $subkey] = explode('.', $key, 2);

                Arr::set($evaluatedArray, $index, [...Arr::get($evaluatedArray, $index, []), $subkey => $value]);
            }
        );
        return $evaluatedArray;
    }

    private function buildFindQuery(array $modelInfo, array $relations = [], bool $canRetunDeleted = false, string $orderBy = null, $having): Builder|ExternalBuilder
    {
        $query = $this->relatedModel::query()
            ->with($relations);

        $this->buildQuery(
            canRetunDeleted: $canRetunDeleted,
            query: $query,
            modelInfo: $modelInfo,
            orderBy: $orderBy,
            having: $having
        );

        return $query;
    }

    private function buildWhereClause(&$query, $key, $value)
    {
        if (isset(class_uses($query->getModel())[HasJsonAttributes::class]))
            if (in_array($key, $query->getModel()->getJsonAtrributes()))
                return;

        if (!is_array($value)) {
            $query->where($key, $value);
            return;
        }

        if (!isset($value[1])) {
            return;
        }

        if (!is_iterable($value[1])) {
            $query->where($key, $value[0], $value[1]);
            return;
        }

        if (strtoupper($value[0]) === 'BETWEEN') {
            $query->whereBetween($key, $value[1]);
            return;
        }

        $query->whereIn($key, $value[1]);
    }

    private function buildWhereHasClause(&$query, $key, $value)
    {
        $query
            ->whereHas($key, fn($qry) => $this->buildQuery(false, $qry, $value));
    }

    private function filterModel(&$modelInfo, $filterNull)
    {
        $ghostModel = $this->relatedModel::newFromStatic($modelInfo);

        $ghostModel->unsetUnkownAttributes();
        $clearModelInfo = $ghostModel->toArray();

        $modelInfo = $filterNull ? Arr::whereNotNull($clearModelInfo) : $clearModelInfo;
    }
}
