<?php

namespace Database\Seeders;

use App\Enums\StatusAtendente;
use App\Models\Atendente;
use App\Models\AtendenteSistema;
use App\Support\SistemaContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AtendenteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // RLS exige sistema_id = contexto atual pra aceitar o insert — sem
        // isso, nem o próprio seeder consegue gravar a linha.
        app(SistemaContext::class)->set('gestao-oficinas');

        $atendente = Atendente::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'atendente@chatservice.test'],
            [
                'sistema_id' => 'gestao-oficinas',
                'nome' => 'Atendente de Teste',
                'senha' => Hash::make('password'),
                'status' => StatusAtendente::Ativo,
            ],
        );

        // Vínculo explícito nos dois sistemas, pra exercitar isolamento:
        // o próprio sistema-base (gestao-oficinas) e um segundo fictício.
        foreach (['gestao-oficinas', 'sistema-ficticio'] as $sistemaId) {
            AtendenteSistema::query()->firstOrCreate([
                'atendente_id' => $atendente->id,
                'sistema_id' => $sistemaId,
            ]);
        }
    }
}
