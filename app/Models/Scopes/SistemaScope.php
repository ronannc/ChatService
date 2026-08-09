<?php

namespace App\Models\Scopes;

use App\Models\Atendente;
use App\Support\SistemaContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SistemaScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(SistemaContext::class);

        // O bypass de resolução de atendente só deve afetar o próprio
        // model Atendente — não Chamado/Mensagem, que não têm relação com
        // esse fluxo de autenticação e devem continuar isolados.
        if ($context->bypassAtivo() && $model instanceof Atendente) {
            return;
        }

        $codigo = $context->get();

        if ($codigo === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('sistema_id'), $codigo);
    }
}
