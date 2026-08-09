<?php

namespace App\Models\Concerns;

use App\Models\Scopes\SistemaScope;
use App\Models\Sistema;
use App\Support\SistemaContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSistema
{
    protected static function bootBelongsToSistema(): void
    {
        static::addGlobalScope(new SistemaScope);

        static::creating(function ($model) {
            if (! $model->sistema_id) {
                $model->sistema_id = app(SistemaContext::class)->get();
            }
        });
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class, 'sistema_id', 'codigo');
    }
}
