<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SistemaContext
{
    private ?string $codigo = null;

    /**
     * Define o sistema atual da request, propagando para a sessão do
     * Postgres (lido pelas policies de Row Level Security via
     * current_setting('app.current_sistema_id', true)).
     */
    public function set(string $codigo): void
    {
        $this->codigo = $codigo;

        DB::statement('SELECT set_config(?, ?, false)', ['app.current_sistema_id', $codigo]);
    }

    /**
     * Limpa o contexto — nenhuma linha das tabelas isoladas deve ser
     * lida ou gravada enquanto não houver um sistema definido.
     */
    public function clear(): void
    {
        $this->codigo = null;

        DB::statement('SELECT set_config(?, ?, false)', ['app.current_sistema_id', '']);
    }

    public function get(): ?string
    {
        return $this->codigo;
    }
}
