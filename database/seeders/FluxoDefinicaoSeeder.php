<?php

namespace Database\Seeders;

use App\Models\FluxoDefinicao;
use Illuminate\Database\Seeder;

/**
 * Fixture MÍNIMA para provar o motor de fluxo fixo ponta a ponta (CHAT-023)
 * — nenhum conteúdo real de fluxo (identificação de problema, feedback)
 * ainda existe. CHAT-024/CHAT-026 substituem esta definição por fluxos de
 * conteúdo de verdade; não estenda este fixture com regras de negócio.
 */
class FluxoDefinicaoSeeder extends Seeder
{
    public function run(): void
    {
        FluxoDefinicao::updateOrCreate(
            ['chave' => FluxoDefinicao::CHAVE_FIXTURE_INICIAL, 'versao' => 1],
            [
                'definicao' => [
                    'no_inicial' => 'inicio',
                    'nos' => [
                        'inicio' => [
                            'tipo' => 'pergunta',
                            'opcoes' => [
                                ['valor' => 'duvida_simples', 'proximo_no' => 'detalhar'],
                                ['valor' => 'falar_com_atendente', 'escalonamento' => true],
                            ],
                        ],
                        'detalhar' => [
                            'tipo' => 'pergunta',
                            'opcoes' => [
                                ['valor' => 'resolvido', 'proximo_no' => 'fim_duvida'],
                                ['valor' => 'falar_com_atendente', 'escalonamento' => true],
                            ],
                        ],
                        'fim_duvida' => [
                            'tipo' => 'fim',
                            'proximo_fluxo' => null,
                        ],
                    ],
                ],
            ],
        );
    }
}
