<?php

namespace App\Support;

/**
 * Valores normativos do contrato do token assinado entre cada sistema
 * integrado e o chat service (CHAT-004). A validação (CHAT-005) deve ler
 * daqui em vez de repetir literais; o documento publicado para os times
 * externos é docs/contratos/token-cliente.md.
 */
final class ContratoTokenCliente
{
    /**
     * Valor literal exigido na claim `aud`. Não é configurável por deploy:
     * o token é emitido para o chat service como serviço, não para uma
     * instalação específica dele.
     */
    public const AUDIENCE = 'chat-service';

    /**
     * Único algoritmo de assinatura aceito. Qualquer outro valor no header
     * (inclusive `none` e algoritmos HMAC) invalida o token — aceitar HMAC
     * permitiria forjar tokens usando a chave pública do JWKS como segredo.
     */
    public const ALGORITMO = 'RS256';

    /** Valor exigido em `typ` no header. */
    public const TIPO_HEADER = 'JWT';

    /**
     * Teto de vida do token, medido por `exp - iat`. Existe para que o
     * emissor não consiga estender a validade indefinidamente.
     */
    public const TTL_MAXIMO_SEGUNDOS = 900;

    public const TTL_RECOMENDADO_SEGUNDOS = 600;

    /**
     * Folga aplicada às comparações de tempo (`exp` e `iat`) para absorver
     * dessincronia de relógio entre o emissor e o chat service.
     */
    public const TOLERANCIA_CLOCK_SKEW_SEGUNDOS = 60;

    public const ROLE_CLIENTE = 'cliente';

    /**
     * Atendente externo (CHAT-005B): mesmo JWT/JWKS do cliente final,
     * diferenciado por esta claim. Aceita a partir de CHAT-005B, mas o
     * papel nunca vale pela claim isolada — `ProvisionarAtendenteExternoService`
     * resolve/cria o atendente a partir do `sub` do token (identidade sem
     * escopo por `iss`, diferente do cliente final) e o vínculo em
     * `atendente_sistema` do sistema emissor; a claim só indica qual
     * verificação fazer.
     */
    public const ROLE_ATENDENTE = 'atendente';

    public const SCOPE_LER = 'chat:ler';

    public const SCOPE_ESCREVER = 'chat:escrever';

    /**
     * Caminho recomendado (não obrigatório) do JWKS. A URL efetiva sempre
     * vem de `sistemas.jwks_url`, nunca de configuração fixa por deploy.
     */
    public const CAMINHO_PADRAO_JWKS = '/.well-known/jwks.json';

    public const TAMANHO_MINIMO_CHAVE_BITS = 2048;

    /**
     * Validade do JWKS em cache. Sem cache, cada request autenticado viraria
     * um fetch HTTPS ao servidor do emissor no caminho mais quente do
     * produto.
     */
    public const TTL_CACHE_JWKS_SEGUNDOS = 600;

    /**
     * Validade do cache negativo: quanto tempo esperar antes de tentar de
     * novo um JWKS que falhou. Evita martelar um emissor fora do ar a cada
     * request.
     */
    public const TTL_CACHE_NEGATIVO_JWKS_SEGUNDOS = 60;

    /**
     * Intervalo mínimo entre dois refetch do JWKS de um mesmo sistema
     * disparados por `kid` desconhecido. Sem esse limite, um atacante manda
     * `kid` aleatório e transforma cada request nossa em uma request HTTP
     * contra o servidor do integrador.
     */
    public const INTERVALO_MINIMO_REFETCH_JWKS_SEGUNDOS = 60;

    public const TIMEOUT_CONEXAO_JWKS_SEGUNDOS = 2;

    public const TIMEOUT_TOTAL_JWKS_SEGUNDOS = 5;

    /** Teto de bytes lidos do JWKS: a URL é de terceiro e pode responder qualquer coisa. */
    public const TAMANHO_MAXIMO_JWKS_BYTES = 65536;

    public const MAXIMO_CHAVES_JWKS = 10;

    /**
     * @return array<int, string>
     */
    public static function scopesConhecidos(): array
    {
        return [self::SCOPE_LER, self::SCOPE_ESCREVER];
    }

    /**
     * Vocabulário completo de `role`, incluindo o que ainda não é aceito.
     *
     * @return array<int, string>
     */
    public static function rolesConhecidos(): array
    {
        return [self::ROLE_CLIENTE, self::ROLE_ATENDENTE];
    }

    /**
     * Papéis aceitos hoje. `role` presente com qualquer outro valor rejeita
     * o token: a claim é controlada inteiramente pelo sistema emissor, e
     * aceitar um papel que nenhum fluxo implementa é abrir caminho para
     * escalação de privilégio. `atendente` (CHAT-005B) nunca vale pela claim
     * isolada — `ProvisionarAtendenteExternoService` resolve/cria o
     * atendente pelo `sub` e o vínculo em `atendente_sistema` a partir do
     * `iss` do token.
     *
     * @return array<int, string>
     */
    public static function rolesAceitos(): array
    {
        return [self::ROLE_CLIENTE, self::ROLE_ATENDENTE];
    }
}
