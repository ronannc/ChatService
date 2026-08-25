<?php

namespace App\Services\Chamado;

use App\Enums\StatusChamado;
use App\Models\Chamado;
use App\Models\FluxoDefinicao;
use App\Services\Auth\TokenClienteValidado;
use App\Services\Fluxo\IniciarFluxoService;

class StoreChamadoService
{
    public function __construct(
        private readonly IniciarFluxoService $iniciarFluxoService,
    ) {}

    /**
     * Cria um chamado para o cliente autenticado. `sistema_id` e
     * `cliente_ref` vêm exclusivamente do token já validado pelo middleware
     * `cliente.token` (`iss` e `sub`), nunca de input do request — um
     * `sistema_id` no body não tem efeito algum aqui.
     *
     * Todo chamado novo já nasce dentro do fluxo fixo (CHAT-023), iniciado
     * com a chave de fixture `FluxoDefinicao::CHAVE_FIXTURE_INICIAL` — não
     * existe ainda nenhum fluxo de conteúdo real (CHAT-024 substitui isto).
     */
    public function handle(TokenClienteValidado $tokenCliente): Chamado
    {
        $chamado = Chamado::create([
            'sistema_id' => $tokenCliente->iss,
            'cliente_ref' => $tokenCliente->sub,
            'status' => StatusChamado::AguardandoFila,
        ]);

        return $this->iniciarFluxoService->handle($chamado, FluxoDefinicao::CHAVE_FIXTURE_INICIAL);
    }
}
