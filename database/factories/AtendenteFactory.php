<?php

namespace Database\Factories;

use App\Models\Atendente;
use App\Models\Sistema;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        ];
    }
}
