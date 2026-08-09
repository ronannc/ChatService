<?php

namespace Database\Seeders;

use App\Enums\StatusSistema;
use App\Models\Sistema;
use Illuminate\Database\Seeder;

class SistemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Sistema::query()->firstOrCreate(
            ['codigo' => 'gestao-oficinas'],
            [
                'nome' => 'Gestão de Oficinas',
                // Placeholder até o CHAT-004/005 definirem a integração real de JWKS.
                'jwks_url' => 'https://gestaodeoficinas.example.com/.well-known/jwks.json',
                'status' => StatusSistema::Ativo,
            ],
        );

        // Segundo sistema fictício — usado só pra testar isolamento
        // (CHAT-003/CHAT-005A), sem integração real nenhuma.
        Sistema::query()->firstOrCreate(
            ['codigo' => 'sistema-ficticio'],
            [
                'nome' => 'Sistema Fictício',
                'jwks_url' => 'https://sistema-ficticio.example.com/.well-known/jwks.json',
                'status' => StatusSistema::Ativo,
            ],
        );
    }
}
