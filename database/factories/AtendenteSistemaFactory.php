<?php

namespace Database\Factories;

use App\Models\Atendente;
use App\Models\AtendenteSistema;
use App\Models\Sistema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AtendenteSistema>
 */
class AtendenteSistemaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'atendente_id' => fn () => Atendente::factory()->create()->id,
            'sistema_id' => fn () => Sistema::factory()->create()->codigo,
        ];
    }
}
