<?php

namespace Database\Factories;

use App\Enums\StatusSistema;
use App\Models\Sistema;
use App\Support\ContratoTokenCliente;
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
            // fake()->url() devolve http:// na maioria das vezes, e o cadastro
            // só aceita https (url:https em StoreSistemaRequest).
            'jwks_url' => 'https://'.fake()->domainName().ContratoTokenCliente::CAMINHO_PADRAO_JWKS,
            'status' => StatusSistema::Ativo,
        ];
    }
}
