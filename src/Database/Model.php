<?php

namespace FimPablo\SigExtenders\Database;

use Carbon\Carbon;
use FimPablo\SigExtenders\Auth\JWTAuth;
use FimPablo\SigExtenders\Utils\Arr;
use Illuminate\Database\Eloquent\Model as ModelEloquent;
use Illuminate\Support\Facades\Schema;

/**
 * Classe responsável por adicionar a model padrão do laravel os métodos e atributos necessários para os sistemas.
 */
class Model extends ModelEloquent
{
    /**
     * prefixo das colunas na base de dados.
     *
     * @var string
     */
    public string $prefixColumns = '';
    public static $snakeAttributes = false;
    public $timestamps = false;
    protected $guarded = [];


    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->unsetUnkownAttributes();
            $userId = JWTAuth::getLoggedUser()?->USUUID ?? 'Adicionado pelo sistema';

            $columnIncluidoPor = "{$model->prefixColumns}INCLUIDOPOR";
            $columnIncluidoEm = "{$model->prefixColumns}INCLUIDOEM";

            $model->$columnIncluidoPor = (string) $userId;
            $model->$columnIncluidoEm = Carbon::now()->format('Y-m-d H:i');

        });

        static::updating(function ($model) {
            $model->unsetUnkownAttributes();

            $userId = 'Alterado pelo sistema';

            if (JWTAuth::getToken() && JWTAuth::getPayload(JWTAuth::getToken())) {
                $userId = JWTAuth::getPayload(JWTAuth::getToken())->get('USUUID');
            }

            $columnAlteradoPor = "{$model->prefixColumns}ALTERADOPOR";
            $columnAlteradoEm = "{$model->prefixColumns}ALTERADOEM";

            $model->$columnAlteradoPor = (string) $userId;
            $model->$columnAlteradoEm = Carbon::now()->format('Y-m-d H:i');
        });

        static::deleting(function ($model) {
            $model->update([
                "{$model->prefixColumns}EXCLUIDO" => 1,
            ]);

            return false;
        });
    }

    public function scopeWhereNotDeleted($query)
    {
        return $query->where("{$this->prefixColumns}EXCLUIDO", '!=', 1);
    }

    private function getColumns()
    {
        return Schema::getColumnListing($this->getTable());
    }

    public function getAllAttributes()
    {
        $attributes = $this->getColumns();
        $mutatedAttributes = $this->getMutatedAttributes();

        $allAttributes = array_merge($attributes, $mutatedAttributes);

        return $allAttributes;
    }

    public function unsetUnkownAttributes()
    {
        $this->attributes = Arr::mapWithKeys(
            $this->attributes,
            /**
             * @param mixed $val
             * @param string $attr
             * @return array
             */
            function ($val, $attr) {
                if (in_array($attr, $this->getAllAttributes())) {
                    return [$attr => $val];
                }

                $attrWPrefix = "{$this->prefixColumns}$attr";
                if (in_array($attrWPrefix, $this->getAllAttributes())) {
                    return [$attrWPrefix => $val];
                }

                return [];
            }
        );
        return $this;
    }

    public static function newFromStatic($attributes = [])
    {
        return (new static($attributes));
    }

    public function getAttribute($key)
    {
        if (array_key_exists($this->prefixColumns . $key, $this->attributes)) {
            return parent::getAttribute($this->prefixColumns . $key);
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if (array_key_exists($this->prefixColumns . $key, $this->attributes)) {
            return parent::setAttribute($this->prefixColumns . $key, $value);
        }

        return parent::setAttribute($key, $value);

    }
}
