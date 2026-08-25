<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Lançada quando `IniciarFluxoService` não encontra nenhuma versão
 * cadastrada para a `chave` de fluxo pedida (CHAT-023) — sempre erro de
 * configuração/deploy (fixture ou fluxo real não seedado), nunca de input
 * do cliente. Distinta de propósito de `ModelNotFoundException` (usada para
 * "chamado não encontrado"/isolamento em `AvancarFluxoService`): a mesma
 * exceção genérica renderizaria 404 para os dois casos e esconderia um erro
 * de operação atrás de um código que devia significar outra coisa.
 */
class FluxoDefinicaoNaoEncontradaException extends RuntimeException
{
    public function __construct(public readonly string $chave)
    {
        parent::__construct("Nenhuma definição de fluxo encontrada para a chave \"{$chave}\".");
    }
}
