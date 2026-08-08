<?php

namespace App\Models;

use App\Enums\StatusSistema;
use Database\Factories\SistemaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sistema extends Model
{
    /** @use HasFactory<SistemaFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'codigo',
        'nome',
        'jwks_url',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusSistema::class,
        ];
    }
}
