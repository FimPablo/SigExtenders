<?php

namespace FimPablo\SigExtenders\Database;

use Carbon\Carbon;
use FimPablo\SigExtenders\Auth\JWTAuth;
use FimPablo\SigExtenders\Utils\Arr;
use Illuminate\Database\Eloquent\Model as ModelEloquent;
use Illuminate\Support\Facades\Schema;

class Model extends ModelEloquent
{
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

            $columnIncluidoPor = $model->prefixColumns . 'INCLUIDOPOR';
            $columnIncluidoEm = $model->prefixColumns . 'INCLUIDOEM';

            $model->$columnIncluidoPor = (string) $userId;
            $model->$columnIncluidoEm = Carbon::now()->format('Y-m-d H:i');

        });

        static::updating(function ($model) {
            $model->unsetUnkownAttributes();

            if (JWTAuth::getToken() && JWTAuth::getPayload(JWTAuth::getToken())) {
                $userId = JWTAuth::getPayload(JWTAuth::getToken())->get('USUUID');
            } else {
                $userId = 'Alterado pelo sistema';
            }

            $columnAlteradoPor = $model->prefixColumns . 'ALTERADOPOR';
            $columnAlteradoEm = $model->prefixColumns . 'ALTERADOEM';

            $model->$columnAlteradoPor = (string) $userId;
            $model->$columnAlteradoEm = Carbon::now()->format('Y-m-d H:i');
        });

        static::deleting(function ($model) {
            $model->update([
                $model->prefixColumns . 'EXCLUIDO' => 1,
            ]);

            return false;
        });
    }

    public function scopeWhereNotDeleted($query)
    {
        return $query->where($this->prefixColumns . 'EXCLUIDO', '!=', 1);
    }

    private function getColumns()
    {
        return Schema::getColumnListing(($this)->getTable());
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
        $this->attributes = Arr::mapWithKeys($this->attributes, function ($val, $attr) {
            if (in_array($attr, $this->getAllAttributes())) {
                return [$attr => $val];
            }

            if (in_array($this->prefixColumns . $attr, $this->getAllAttributes())) {
                return [$this->prefixColumns . $attr => $val];
            }

            return [];
        });
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
