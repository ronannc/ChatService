<?php

namespace Tests\Support;

use App\Enums\ClaimTokenCliente;
use App\Support\ContratoTokenCliente;

/**
 * Emissor de exemplos do contrato do token (docs/contratos/token-cliente.md):
 * um token que cumpre o contrato e um por motivo de invalidez previsto.
 * Reaproveitado pelos testes de validação (CHAT-005).
 *
 * Os tokens são montados em tempo de execução em vez de versionados como
 * strings porque `exp` é relativo ao agora — um JWT versionado expiraria e
 * quebraria a suíte. O que está versionado é o par de chaves em
 * tests/Fixtures/Token, usado **exclusivamente** por testes: nenhuma delas
 * tem valor fora da suíte e nenhuma é aceita em qualquer ambiente real.
 *
 * A assinatura é feita com openssl_* nativo, de propósito: assim os exemplos
 * não dependem da mesma biblioteca que fará a validação, e um bug nela não
 * passa despercebido por o gerador e o validador concordarem entre si.
 */
class GeradorTokenTeste
{
    public const KID = 'chat-service-teste-2026';

    /** Corresponde ao sistema cadastrado pelo SistemaSeeder. */
    public const SISTEMA_CODIGO = 'gestao-oficinas';

    public const SISTEMA_NAO_CADASTRADO = 'sistema-jamais-cadastrado';

    public const SUB = '4213';

    public const SCOPE = ContratoTokenCliente::SCOPE_LER.' '.ContratoTokenCliente::SCOPE_ESCREVER;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function claimsValidas(array $overrides = []): array
    {
        $agora = time();

        return [
            ...[
                ClaimTokenCliente::Iss->value => self::SISTEMA_CODIGO,
                ClaimTokenCliente::Sub->value => self::SUB,
                ClaimTokenCliente::Aud->value => ContratoTokenCliente::AUDIENCE,
                ClaimTokenCliente::Scope->value => self::SCOPE,
                ClaimTokenCliente::Iat->value => $agora,
                ClaimTokenCliente::Exp->value => $agora + ContratoTokenCliente::TTL_RECOMENDADO_SEGUNDOS,
            ],
            ...$overrides,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function headerValido(): array
    {
        return [
            'alg' => ContratoTokenCliente::ALGORITMO,
            'typ' => 'JWT',
            'kid' => self::KID,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public static function valido(array $overrides = []): string
    {
        return self::assinar(self::headerValido(), self::claimsValidas($overrides));
    }

    public static function semClaim(ClaimTokenCliente $claim): string
    {
        $claims = self::claimsValidas();
        unset($claims[$claim->value]);

        return self::assinar(self::headerValido(), $claims);
    }

    public static function expirado(): string
    {
        $agora = time();

        return self::valido([
            ClaimTokenCliente::Iat->value => $agora - 700,
            ClaimTokenCliente::Exp->value => $agora - 100,
        ]);
    }

    public static function audienciaErrada(): string
    {
        return self::valido([ClaimTokenCliente::Aud->value => 'outro-servico']);
    }

    public static function scopeVazio(): string
    {
        return self::valido([ClaimTokenCliente::Scope->value => '']);
    }

    /**
     * `iat` adiante no tempo com `exp` logo depois: burla o teto de TTL
     * (`exp - iat` continua dentro do limite) e estende a validade real do
     * token indefinidamente.
     */
    public static function iatNoFuturo(): string
    {
        $futuro = time() + 3600;

        return self::valido([
            ClaimTokenCliente::Iat->value => $futuro,
            ClaimTokenCliente::Exp->value => $futuro + ContratoTokenCliente::TTL_RECOMENDADO_SEGUNDOS,
        ]);
    }

    /** `aud` precisa ser string; a forma em array da RFC 7519 é rejeitada. */
    public static function audComoArray(): string
    {
        return self::valido([
            ClaimTokenCliente::Aud->value => [ContratoTokenCliente::AUDIENCE, 'outro-servico'],
        ]);
    }

    /** Erro comum de emissor: `exp` serializado como string em vez de número. */
    public static function expComoString(): string
    {
        return self::valido([ClaimTokenCliente::Exp->value => (string) (time() + 600)]);
    }

    /** Papel fora do vocabulário do contrato. */
    public static function roleNaoReconhecida(): string
    {
        return self::valido([ClaimTokenCliente::Role->value => 'supervisor']);
    }

    /**
     * Papel do vocabulário porém não aceito hoje: enquanto CHAT-005B não
     * existir, qualquer `role` diferente de `cliente` rejeita o token, para
     * que uma claim controlada pelo emissor não vire escalação de
     * privilégio. Ver ContratoTokenCliente::rolesAceitos().
     */
    public static function rolePapelAtendente(): string
    {
        return self::valido([ClaimTokenCliente::Role->value => ContratoTokenCliente::ROLE_ATENDENTE]);
    }

    /**
     * Token com `role=atendente` (CHAT-005B), aceito hoje pelo contrato e
     * usado pelo fluxo de provisionamento just-in-time de atendente externo
     * (`ProvisionarAtendenteExternoService`). Aceita overrides (ex.: `iss`
     * ou `sub` diferentes) para exercitar múltiplos sistemas/identidades.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function papelAtendente(array $overrides = []): string
    {
        return self::valido([
            ClaimTokenCliente::Role->value => ContratoTokenCliente::ROLE_ATENDENTE,
            ...$overrides,
        ]);
    }

    /** Duas partes: falta a assinatura. */
    public static function comDuasPartes(): string
    {
        $partes = explode('.', self::valido());

        return $partes[0].'.'.$partes[1];
    }

    public static function comCincoPartes(): string
    {
        return self::valido().'.parte.extra';
    }

    public static function payloadNaoJson(): string
    {
        $partes = explode('.', self::valido());

        return $partes[0].'.'.self::base64UrlEncode('isto não é json').'.'.$partes[2];
    }

    public static function typInvalido(): string
    {
        return self::assinar([...self::headerValido(), 'typ' => 'JWS'], self::claimsValidas());
    }

    public static function ttlAcimaDoMaximo(): string
    {
        $agora = time();

        return self::valido([
            ClaimTokenCliente::Iat->value => $agora,
            ClaimTokenCliente::Exp->value => $agora + ContratoTokenCliente::TTL_MAXIMO_SEGUNDOS + 1,
        ]);
    }

    /** Token estruturalmente perfeito cujo `iss` não existe na tabela `sistemas`. */
    public static function issSistemaNaoCadastrado(): string
    {
        return self::valido([ClaimTokenCliente::Iss->value => self::SISTEMA_NAO_CADASTRADO]);
    }

    /** Token estruturalmente perfeito emitido por um sistema com status inativo. */
    public static function issSistemaInativo(string $codigo): string
    {
        return self::valido([ClaimTokenCliente::Iss->value => $codigo]);
    }

    /** Assinado por uma chave que não está publicada no JWKS do sistema. */
    public static function assinadoPorOutraChave(): string
    {
        return self::assinar(
            self::headerValido(),
            self::claimsValidas(),
            self::fixture('chave-privada-outro-emissor-teste.pem'),
        );
    }

    /** Aponta para uma chave que o JWKS do sistema não publica. */
    public static function kidDesconhecido(): string
    {
        return self::assinar(
            [...self::headerValido(), 'kid' => 'kid-que-nao-esta-no-jwks'],
            self::claimsValidas(),
        );
    }

    public static function semKid(): string
    {
        $header = self::headerValido();
        unset($header['kid']);

        return self::assinar($header, self::claimsValidas());
    }

    /** Tentativa clássica de burlar a verificação removendo a assinatura. */
    public static function algNone(): string
    {
        $header = [...self::headerValido(), 'alg' => 'none'];

        return self::conteudoAssinavel($header, self::claimsValidas()).'.';
    }

    /**
     * Confusão de algoritmo: o atacante assina com HMAC usando a chave
     * pública do JWKS (que é pública) como segredo. Só é barrado porque o
     * contrato fixa RS256 em vez de confiar no `alg` do header.
     */
    public static function algHs256ComChavePublicaComoSegredo(): string
    {
        $header = [...self::headerValido(), 'alg' => 'HS256'];
        $conteudo = self::conteudoAssinavel($header, self::claimsValidas());

        return $conteudo.'.'.self::base64UrlEncode(
            hash_hmac('sha256', $conteudo, self::chavePublicaPem(), true),
        );
    }

    public static function chavePrivadaPem(): string
    {
        return self::fixture('chave-privada-teste.pem');
    }

    public static function chavePublicaPem(): string
    {
        return self::fixture('chave-publica-teste.pem');
    }

    /**
     * @return array{keys: array<int, array<string, string>>}
     */
    public static function jwks(): array
    {
        return json_decode(self::fixture('jwks-teste.json'), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * JWKS publicando uma chave RSA de 1024 bits, abaixo do mínimo exigido
     * pelo contrato. Serve para CHAT-005 exercitar a recusa na etapa de
     * busca do JWKS — a fraqueza está no conjunto de chaves do sistema, não
     * em nada que se possa observar no token.
     *
     * @return array{keys: array<int, array<string, string>>}
     */
    public static function jwksChaveFraca(): array
    {
        return json_decode(self::fixture('jwks-chave-fraca-teste.json'), true, flags: JSON_THROW_ON_ERROR);
    }

    public static function base64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $texto): string
    {
        return base64_decode(strtr($texto, '-_', '+/').str_repeat('=', (4 - strlen($texto) % 4) % 4), true);
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  array<string, mixed>  $claims
     */
    private static function assinar(array $header, array $claims, ?string $chavePrivadaPem = null): string
    {
        $conteudo = self::conteudoAssinavel($header, $claims);

        openssl_sign($conteudo, $assinatura, $chavePrivadaPem ?? self::chavePrivadaPem(), OPENSSL_ALGO_SHA256);

        return $conteudo.'.'.self::base64UrlEncode($assinatura);
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  array<string, mixed>  $claims
     */
    private static function conteudoAssinavel(array $header, array $claims): string
    {
        return self::base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES))
            .'.'
            .self::base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private static function fixture(string $arquivo): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/Token/'.$arquivo);
    }
}
