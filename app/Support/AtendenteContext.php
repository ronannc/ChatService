<?php

namespace App\Support;

use App\Models\Atendente;
use Illuminate\Support\Collection;

class AtendenteContext
{
    private ?Atendente $atendente = null;

    public function set(Atendente $atendente): void
    {
        $this->atendente = $atendente;
    }

    public function atendente(): ?Atendente
    {
        return $this->atendente;
    }

    /**
     * Códigos de sistema aos quais o atendente atual tem permissão — o
     * sistema-base do próprio atendente mais os concedidos via
     * atendente_sistema.
     *
     * @return Collection<int, string>
     */
    public function sistemasPermitidos(): Collection
    {
        if (! $this->atendente) {
            return collect();
        }

        return $this->atendente->sistemasComPermissao()
            ->pluck('sistema_id')
            ->push($this->atendente->sistema_id)
            ->unique()
            ->values();
    }
}
