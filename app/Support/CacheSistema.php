<?php

namespace App\Support;

use App\Models\Sistema;
use Illuminate\Support\Facades\Cache;

/**
 * Cache do cadastro de `sistemas`, com invalidação explícita (§3.4 do
 * contrato do token do cliente) em vez de TTL: a validação de token consulta
 * o cadastro antes de checar a assinatura, e desativar um sistema precisa
 * derrubar o acesso imediatamente, não esperar um TTL expirar.
 *
 * `StoreSistemaService`/`UpdateSistemaService` chamam `esquecer()` sempre
 * que gravam `status`/`jwks_url`.
 */
class CacheSistema
{
    public static function chave(string $codigo): string
    {
        return "sistema:codigo:{$codigo}";
    }

    /**
     * Busca o sistema pelo `codigo`, cacheando o resultado indefinidamente
     * até invalidação explícita. Um `codigo` sem sistema correspondente não
     * é cacheado — isso evitaria que um sistema recém-cadastrado passasse a
     * responder antes do TTL de um cache negativo expirar, e o custo de um
     * `SELECT` extra recai só sobre tokens com `iss` desconhecido.
     */
    public static function buscarPorCodigo(string $codigo): ?Sistema
    {
        return Cache::rememberForever(
            self::chave($codigo),
            fn (): ?Sistema => Sistema::where('codigo', $codigo)->first(),
        );
    }

    public static function esquecer(string $codigo): void
    {
        Cache::forget(self::chave($codigo));
    }
}
