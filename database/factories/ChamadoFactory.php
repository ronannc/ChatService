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
            'status' => StatusChamado::AguardandoFila,
        ];
    }
}
