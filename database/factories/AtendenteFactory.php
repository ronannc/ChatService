<?php

namespace Database\Factories;

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
        ];
    }
}
