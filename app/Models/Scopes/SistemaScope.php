<?php

namespace App\Models\Scopes;

use App\Support\SistemaContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SistemaScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $codigo = app(SistemaContext::class)->get();

        if ($codigo === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('sistema_id'), $codigo);
    }
}
