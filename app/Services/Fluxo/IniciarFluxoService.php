<?php

namespace App\Services\Fluxo;

use App\Enums\StatusChamado;
use App\Exceptions\FluxoDefinicaoNaoEncontradaException;
use App\Models\Chamado;
use App\Models\FluxoDefinicao;

class IniciarFluxoService
{
    /**
     * Resolve a versão vigente da definição pela `chave` e "prende" o
     * chamado a essa versão exata (`fluxo_definicao_id`) — se a definição
     * "current" da mesma chave mudar depois, este chamado não é afetado
     * (não há aqui, nem em `AvancarFluxoService`, nenhuma leitura pela
     * chave; sempre pelo id já gravado).
     *
     * Reaproveitado por `AvancarFluxoService` para encadear o próximo fluxo
     * dentro do MESMO chamado quando um nó tipo "fim" aponta para outra
     * chave — por isso não mexe em `respostas_coletadas`, que precisa
     * acumular entre fluxos encadeados.
     */
    public function handle(Chamado $chamado, string $chaveFluxo): Chamado
    {
        $definicao = FluxoDefinicao::where('chave', $chaveFluxo)
            ->orderByDesc('versao')
            ->first();

        if (! $definicao) {
            throw new FluxoDefinicaoNaoEncontradaException($chaveFluxo);
        }

        $chamado->update([
            'fluxo_definicao_id' => $definicao->id,
            'no_atual' => $definicao->definicao['no_inicial'],
            'status' => StatusChamado::FluxoEmAndamento,
        ]);

        return $chamado;
    }
}
