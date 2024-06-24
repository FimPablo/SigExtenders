<?php

namespace FimPablo\SigExtenders\Database\Traits;

trait TenantUuid
{
    public static function bootSetsTenantUuid(): void
    {
        static::creating(function ($model) {
            $model->tenant_id = tenant('uuid');
        });
    }

}
