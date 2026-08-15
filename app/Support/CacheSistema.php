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
     *
     * Cacheia os atributos crus (array), não o Model Eloquent inteiro: o
     * store `redis` (config/cache.php) não define `serialize`, e a partir da
     * segunda leitura o `unserialize()` de um Model (que carrega closures em
     * `$classCastCache`/relações internamente ou, no caso comum, simplesmente
     * o objeto sem `allowed_classes`) volta como `__PHP_Incomplete_Class` —
     * quebra `ValidarTokenClienteService` com `TypeError` já na segunda
     * validação de token do mesmo `iss`. Reconstruir via
     * `Sistema::newFromBuilder()` a partir de um array evita depender de
     * como o driver de cache serializa objetos.
     */
    public static function buscarPorCodigo(string $codigo): ?Sistema
    {
        $atributos = Cache::rememberForever(
            self::chave($codigo),
            fn (): ?array => Sistema::where('codigo', $codigo)->first()?->getAttributes(),
        );

        return $atributos === null ? null : (new Sistema)->newFromBuilder($atributos);
    }

    public static function esquecer(string $codigo): void
    {
        Cache::forget(self::chave($codigo));
    }
}
