<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSistema;
use Database\Factories\MensagemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mensagem extends Model
{
    /** @use HasFactory<MensagemFactory> */
    use BelongsToSistema, HasFactory;

    protected $table = 'mensagens';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sistema_id',
        'chamado_id',
    ];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class);
    }
}
