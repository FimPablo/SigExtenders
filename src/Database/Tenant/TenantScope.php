<?php

declare(strict_types=1);

namespace FimPablo\SigExtenders\Database\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Stancl\Tenancy\Database\TenantScope as DefaultTenantScope;

class TenantScope extends DefaultTenantScope
{
    public function apply(Builder $builder, Model $model)
    {
        if (!tenancy()->initialized) {
            return;
        }

        $builder->where(function ($qry) use ($model) {
            $tenantCol = $model->qualifyColumn(BelongsToTenant::$tenantIdColumn);

            $qry->where($tenantCol, tenant()->getTenantKey())
                ->orWhere($tenantCol, null);
        });
    }
}