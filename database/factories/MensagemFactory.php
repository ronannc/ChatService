<?php

namespace Database\Factories;

use App\Enums\RemetenteMensagem;
use App\Models\Chamado;
use App\Models\Mensagem;
use App\Support\SistemaContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mensagem>
 */
class MensagemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Cria o chamado sob o mesmo sistema do contexto atual (se
            // houver) — sem isso, o RLS rejeita o insert do próprio chamado
            // sempre que o teste já tiver definido um contexto diferente do
            // sistema aleatório que o ChamadoFactory geraria por padrão.
            'chamado_id' => fn () => Chamado::factory()->create([
                'sistema_id' => app(SistemaContext::class)->get(),
            ])->id,
            'sistema_id' => function (array $attributes) {
                return Chamado::withoutGlobalScopes()->find($attributes['chamado_id'])->sistema_id;
            },
            'texto' => fake()->sentence(),
            'remetente_tipo' => RemetenteMensagem::Cliente,
            'remetente_ref' => fake()->uuid(),
        ];
    }
}
