<?php

namespace App\Services\Auth;

use App\Enums\ClaimTokenCliente;
use App\Enums\StatusSistema;
use App\Exceptions\TokenClienteInvalidoException;
use App\Models\Sistema;
use App\Support\CacheSistema;
use App\Support\ContratoTokenCliente;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Log;
use OpenSSLAsymmetricKey;

/**
 * Validação do token do cliente final assinado por um sistema integrado
 * (docs/contratos/token-cliente.md). Implementa, na ordem exigida pelo
 * contrato, cada um dos motivos fechados de invalidez da §4 — nenhum motivo
 * fora dessa lista, nenhum pulado.
 *
 * A ordem importa: `alg` e `kid` são checados antes de qualquer verificação
 * de assinatura (é o que barra confusão de algoritmo), uma claim com tipo
 * errado nunca chega a ser avaliada pelas regras de valor, e o cadastro do
 * sistema (`iss` conhecido e ativo) é resolvido antes de buscar o JWKS —
 * sem sistema ativo não há JWKS a consultar.
 *
 * Todo motivo de rejeição vira a mesma exceção genérica
 * (`TokenClienteInvalidoException`); o motivo específico só é logado, nunca
 * devolvido ao chamador.
 */
class ValidarTokenClienteService
{
    public function __construct(private readonly RepositorioJwks $jwks) {}

    public function handle(string $token): TokenClienteValidado
    {
        try {
            return $this->validar($token);
        } catch (TokenClienteInvalidoException $e) {
            Log::warning('token_cliente.rejeitado', ['motivo' => $e->motivo]);

            throw $e;
        }
    }

    private function validar(string $token): TokenClienteValidado
    {
        $partes = explode('.', $token);

        if (count($partes) !== 3) {
            throw new TokenClienteInvalidoException('formato_invalido');
        }

        [$headerB64, $payloadB64, $assinaturaB64] = $partes;

        $header = $this->decodificarJson($headerB64);
        $claims = $this->decodificarJson($payloadB64);

        $this->verificarAlgoritmo($header);
        $this->verificarTyp($header);
        $kid = $this->verificarKid($header);

        $this->verificarClaimsObrigatorias($claims);
        $this->verificarAudiencia($claims);
        $this->verificarTempo($claims);
        $this->verificarRole($claims);

        $sistema = $this->resolverSistemaAtivo($claims[ClaimTokenCliente::Iss->value]);

        $chave = $this->resolverChaveDeAssinatura($sistema, $kid);

        $this->verificarAssinatura($headerB64.'.'.$payloadB64, $assinaturaB64, $chave);

        return new TokenClienteValidado(
            iss: $sistema->codigo,
            sub: $claims[ClaimTokenCliente::Sub->value],
            scope: $claims[ClaimTokenCliente::Scope->value],
            claims: $claims,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodificarJson(string $base64Url): array
    {
        $json = $this->base64UrlDecode($base64Url);
        $dados = $json === false ? null : json_decode($json, true);

        if (! is_array($dados)) {
            throw new TokenClienteInvalidoException('formato_invalido');
        }

        return $dados;
    }

    /**
     * @param  array<string, mixed>  $header
     */
    private function verificarAlgoritmo(array $header): void
    {
        if (($header['alg'] ?? null) !== ContratoTokenCliente::ALGORITMO) {
            throw new TokenClienteInvalidoException('algoritmo_nao_suportado');
        }
    }

    /**
     * @param  array<string, mixed>  $header
     */
    private function verificarTyp(array $header): void
    {
        if (($header['typ'] ?? null) !== ContratoTokenCliente::TIPO_HEADER) {
            throw new TokenClienteInvalidoException('typ_invalido');
        }
    }

    /**
     * @param  array<string, mixed>  $header
     */
    private function verificarKid(array $header): string
    {
        $kid = $header['kid'] ?? null;

        if (! is_string($kid) || trim($kid) === '') {
            throw new TokenClienteInvalidoException('kid_ausente');
        }

        return $kid;
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function verificarClaimsObrigatorias(array $claims): void
    {
        foreach (ClaimTokenCliente::obrigatorias() as $claim) {
            if (! array_key_exists($claim->value, $claims)) {
                throw new TokenClienteInvalidoException("claim_ausente:{$claim->value}");
            }

            $valor = $claims[$claim->value];

            // Os tipos são exigidos, não coagidos: uma claim com tipo
            // errado não passa a ser avaliada pelas regras de valor
            // seguintes (ex.: "exp" como string não chega a ser comparado
            // com a data atual).
            if (get_debug_type($valor) !== $claim->tipo()) {
                throw new TokenClienteInvalidoException("claim_tipo_invalido:{$claim->value}");
            }

            if ($claim->tipo() === 'string' && trim($valor) === '') {
                throw new TokenClienteInvalidoException("claim_vazia:{$claim->value}");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function verificarAudiencia(array $claims): void
    {
        if ($claims[ClaimTokenCliente::Aud->value] !== ContratoTokenCliente::AUDIENCE) {
            throw new TokenClienteInvalidoException('audiencia_invalida');
        }
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function verificarTempo(array $claims): void
    {
        $exp = $claims[ClaimTokenCliente::Exp->value];
        $iat = $claims[ClaimTokenCliente::Iat->value];
        $agora = now()->timestamp;
        $tolerancia = ContratoTokenCliente::TOLERANCIA_CLOCK_SKEW_SEGUNDOS;

        if ($agora > $exp + $tolerancia) {
            throw new TokenClienteInvalidoException('expirado');
        }

        // Sem esta checagem o teto de TTL não vale nada: "iat" lá na frente
        // com "exp = iat + 600" mantém "exp - iat" dentro do limite e
        // estende a validade real do token indefinidamente.
        if ($iat > $agora + $tolerancia) {
            throw new TokenClienteInvalidoException('iat_no_futuro');
        }

        if ($exp - $iat > ContratoTokenCliente::TTL_MAXIMO_SEGUNDOS) {
            throw new TokenClienteInvalidoException('ttl_acima_do_maximo');
        }
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function verificarRole(array $claims): void
    {
        if (! array_key_exists(ClaimTokenCliente::Role->value, $claims)) {
            return;
        }

        if (! in_array($claims[ClaimTokenCliente::Role->value], ContratoTokenCliente::rolesAceitos(), true)) {
            throw new TokenClienteInvalidoException('role_nao_aceita');
        }
    }

    private function resolverSistemaAtivo(string $iss): Sistema
    {
        $sistema = CacheSistema::buscarPorCodigo($iss);

        if ($sistema === null) {
            throw new TokenClienteInvalidoException('iss_nao_cadastrado');
        }

        if ($sistema->status !== StatusSistema::Ativo) {
            throw new TokenClienteInvalidoException('sistema_inativo');
        }

        return $sistema;
    }

    private function resolverChaveDeAssinatura(Sistema $sistema, string $kid): OpenSSLAsymmetricKey
    {
        $jwks = $this->jwks->obterParaKid($sistema, $kid);

        $jwk = null;

        foreach ($jwks['keys'] as $candidata) {
            // Chaves com kty diferente de RSA ou alg diferente de RS256 são
            // ignoradas (§3 do contrato) — um kid que só existe numa chave
            // assim é, na prática, um kid não encontrado.
            if (($candidata['kid'] ?? null) === $kid
                && ($candidata['kty'] ?? null) === 'RSA'
                && ($candidata['alg'] ?? null) === ContratoTokenCliente::ALGORITMO) {
                $jwk = $candidata;

                break;
            }
        }

        if ($jwk === null) {
            throw new TokenClienteInvalidoException('kid_nao_encontrado');
        }

        $chave = JWK::parseKey($jwk, ContratoTokenCliente::ALGORITMO)?->getKeyMaterial();

        if (! $chave instanceof OpenSSLAsymmetricKey) {
            throw new TokenClienteInvalidoException('kid_nao_encontrado');
        }

        $detalhes = openssl_pkey_get_details($chave);

        if (($detalhes['bits'] ?? 0) < ContratoTokenCliente::TAMANHO_MINIMO_CHAVE_BITS) {
            throw new TokenClienteInvalidoException('chave_abaixo_do_minimo');
        }

        return $chave;
    }

    private function verificarAssinatura(string $conteudoAssinado, string $assinaturaB64, OpenSSLAsymmetricKey $chave): void
    {
        $assinatura = $this->base64UrlDecode($assinaturaB64);

        if ($assinatura === false
            || openssl_verify($conteudoAssinado, $assinatura, $chave, OPENSSL_ALGO_SHA256) !== 1) {
            throw new TokenClienteInvalidoException('assinatura_invalida');
        }
    }

    private function base64UrlDecode(string $texto): string|false
    {
        return base64_decode(strtr($texto, '-_', '+/').str_repeat('=', (4 - strlen($texto) % 4) % 4), true);
    }
}
