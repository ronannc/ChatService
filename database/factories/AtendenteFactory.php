<?php

namespace Database\Factories;

use App\Enums\OrigemAtendente;
use App\Enums\StatusAtendente;
use App\Models\Atendente;
use App\Models\Sistema;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Atendente>
 */
class AtendenteFactory extends Factory
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
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'senha' => Hash::make('password'),
            'status' => StatusAtendente::Ativo,
            'sub_externo' => null,
            'origem' => OrigemAtendente::Interno,
        ];
    }

    /**
     * Atendente externo (CHAT-005B): provisionado just-in-time a partir de
     * um JWT com `role=atendente`, sem e-mail/senha — a identidade é o par
     * `(sistema_id, sub_externo)`.
     */
    public function externo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email' => null,
            'senha' => null,
            'sub_externo' => (string) fake()->unique()->numerify('externo-####'),
            'origem' => OrigemAtendente::Externo,
        ]);
    }
}
