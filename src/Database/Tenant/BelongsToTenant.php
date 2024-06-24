<?php

declare(strict_types=1);

namespace FimPablo\SigExtenders\Database\Tenant;

use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant as DefaultBelongsToTenant;

/**
 * @property-read Tenant $tenant
 */
trait BelongsToTenant
{
    use DefaultBelongsToTenant;

    public static function bootBelongsToTenant()
    {
        BelongsToTenant::$tenantIdColumn = 'tenant_uuid';
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (!$model->getAttribute(BelongsToTenant::$tenantIdColumn) && !$model->relationLoaded('tenant')) {
                if (tenancy()->initialized) {
                    $model->setAttribute(BelongsToTenant::$tenantIdColumn, tenant()->getTenantKey());
                    // $model->setRelation('tenant', tenant()); tem pra que retornar o Teant? acho q n...
                }
            }
        });

        static::updating(function ($model) {
            if ($model->{BelongsToTenant::$tenantIdColumn} === null) {
                throw new \Exception("Can not update global database entries", 400);
            }
        });

        static::deleting(function ($model) {
            if ($model->{BelongsToTenant::$tenantIdColumn} === null) {
                throw new \Exception("Can not delete global database entries", 400);
            }
        });
    }
}