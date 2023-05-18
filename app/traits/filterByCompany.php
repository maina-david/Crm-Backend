<?php

namespace App\traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait filterByCompany
{
    protected static function boot()
    {
        parent::boot();

        if (Auth::user() && Auth::user()->tokenCan('role:customer')) {
            self::creating(function ($model) {
                $model->company_id = Auth::user()->company_id;
            });

            self::addGlobalScope(function (Builder $builder) {
                $builder->where('company_id', Auth::user()->company_id);
            });
        }
    }
}