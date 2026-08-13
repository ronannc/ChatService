<?php

namespace Database\Factories;

use App\Enums\StatusChamado;
use App\Models\Chamado;
use App\Models\Sistema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chamado>
 */
class ChamadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sistema_id' => fn () => Sistema::factory()->create()->codigo,
            'cliente_ref' => null,
            'status' => StatusChamado::AguardandoFila,
        ];
    }

    /**
     * Estado com `cliente_ref` preenchido — útil pros testes de autorização
     * de canal (CHAT-006), onde o chamado precisa casar com o `sub` de um
     * token de cliente.
     */
    public function comClienteRef(string $clienteRef): static
    {
        return $this->state(fn () => ['cliente_ref' => $clienteRef]);
    }
}
