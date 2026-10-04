<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Tenantable
{
    protected static function bootTenantable(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->hasUser() && !auth()->user()->hasRole('Super Admin')) {
                $builder->where('company_id', auth()->user()->company_id);
            }
        });
    }
}
