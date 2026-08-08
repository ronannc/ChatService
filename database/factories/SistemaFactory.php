<?php

namespace Database\Factories;

use App\Enums\StatusSistema;
use App\Models\Sistema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sistema>
 */
class SistemaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->slug(2),
            'nome' => fake()->company(),
            'jwks_url' => fake()->url(),
            'status' => StatusSistema::Ativo,
        ];
    }
}
