<?php

use App\Enums\ClaimTokenCliente;
use App\Support\ContratoTokenCliente;
use Tests\Support\GeradorTokenTeste;

/**
 * Espelha os motivos de invalidez listados em docs/contratos/token-cliente.md.
 * Não é a validação de produção — essa é CHAT-005. Serve para provar que cada
 * exemplo viola exatamente o item que se propõe a violar, e nenhum outro, de
 * modo que CHAT-005 possa testar um motivo de rejeição por vez sem falso
 * positivo.
 *
 * @return array<int, string>
 */
function motivosDeInvalidez(string $token): array
{
    $partes = explode('.', $token);

    if (count($partes) !== 3) {
        return ['formato_invalido'];
    }

    $header = json_decode(GeradorTokenTeste::base64UrlDecode($partes[0]), true);
    $claims = json_decode(GeradorTokenTeste::base64UrlDecode($partes[1]), true);

    if (! is_array($header) || ! is_array($claims)) {
        return ['formato_invalido'];
    }

    $motivos = [];
    $chave = chavePublicaDoHeader($header);

    if (($header['alg'] ?? null) !== ContratoTokenCliente::ALGORITMO) {
        $motivos[] = 'algoritmo_nao_suportado';
    } elseif (! isset($header['kid'])) {
        $motivos[] = 'kid_ausente';
    } elseif ($chave === null) {
        $motivos[] = 'kid_nao_encontrado_no_jwks';
    }

    if (($header['typ'] ?? null) !== ContratoTokenCliente::TIPO_HEADER) {
        $motivos[] = 'typ_invalido';
    }

    // Uma claim com o tipo errado não é coagida: o valor não é avaliado
    // pelas regras seguintes, senão o mesmo exemplo acusaria dois motivos.
    $tipoCorreto = [];

    foreach (ClaimTokenCliente::obrigatorias() as $claim) {
        $valor = $claims[$claim->value] ?? null;

        if (! isset($claims[$claim->value])) {
            $motivos[] = 'claim_ausente:'.$claim->value;
        } elseif (get_debug_type($valor) !== $claim->tipo()) {
            $motivos[] = 'claim_tipo_invalido:'.$claim->value;
        } elseif ($claim->tipo() === 'string' && trim($valor) === '') {
            $motivos[] = 'claim_vazia:'.$claim->value;
        } else {
            $tipoCorreto[$claim->value] = $valor;
        }
    }

    if (isset($tipoCorreto['aud']) && $tipoCorreto['aud'] !== ContratoTokenCliente::AUDIENCE) {
        $motivos[] = 'audiencia_invalida';
    }

    if (isset($tipoCorreto['exp'])
        && time() > $tipoCorreto['exp'] + ContratoTokenCliente::TOLERANCIA_CLOCK_SKEW_SEGUNDOS) {
        $motivos[] = 'expirado';
    }

    // Sem esta checagem o teto de TTL não vale nada: `iat` lá na frente com
    // `exp = iat + 600` mantém `exp - iat` dentro do limite e estende a
    // validade real do token indefinidamente.
    if (isset($tipoCorreto['iat'])
        && $tipoCorreto['iat'] > time() + ContratoTokenCliente::TOLERANCIA_CLOCK_SKEW_SEGUNDOS) {
        $motivos[] = 'iat_no_futuro';
    }

    if (isset($tipoCorreto['exp'], $tipoCorreto['iat'])
        && $tipoCorreto['exp'] - $tipoCorreto['iat'] > ContratoTokenCliente::TTL_MAXIMO_SEGUNDOS) {
        $motivos[] = 'ttl_acima_do_maximo';
    }

    if (isset($claims['role']) && ! in_array($claims['role'], ContratoTokenCliente::rolesAceitos(), true)) {
        $motivos[] = 'role_nao_aceita';
    }

    // A assinatura só é verificada quando o header indica um algoritmo
    // permitido e um kid resolvível — é assim que a validação real evita
    // confusão de algoritmo, e sem isso um mesmo exemplo acusaria dois
    // motivos ao mesmo tempo.
    if ($chave !== null && ($header['alg'] ?? null) === ContratoTokenCliente::ALGORITMO) {
        $conteudo = $partes[0].'.'.$partes[1];
        $assinatura = GeradorTokenTeste::base64UrlDecode($partes[2]);

        if (openssl_verify($conteudo, $assinatura, $chave, OPENSSL_ALGO_SHA256) !== 1) {
            $motivos[] = 'assinatura_invalida';
        }
    }

    return $motivos;
}

/**
 * @param  array<string, mixed>  $header
 */
function chavePublicaDoHeader(array $header): ?string
{
    $kids = array_column(GeradorTokenTeste::jwks()['keys'], 'kid');

    return in_array($header['kid'] ?? null, $kids, true)
        ? GeradorTokenTeste::chavePublicaPem()
        : null;
}

test('o jwks versionado publica a mesma chave do par de teste', function () {
    $detalhes = openssl_pkey_get_details(openssl_pkey_get_public(GeradorTokenTeste::chavePublicaPem()));
    $jwk = GeradorTokenTeste::jwks()['keys'][0];

    expect($jwk['n'])->toBe(GeradorTokenTeste::base64UrlEncode($detalhes['rsa']['n']))
        ->and($jwk['e'])->toBe(GeradorTokenTeste::base64UrlEncode($detalhes['rsa']['e']))
        ->and($jwk['kid'])->toBe(GeradorTokenTeste::KID);
});

test('o jwks versionado tem o formato que o contrato exige do sistema integrado', function () {
    $jwk = GeradorTokenTeste::jwks()['keys'][0];

    expect($jwk)->toHaveKeys(['kty', 'use', 'alg', 'kid', 'n', 'e'])
        ->and($jwk['kty'])->toBe('RSA')
        ->and($jwk['use'])->toBe('sig')
        ->and($jwk['alg'])->toBe(ContratoTokenCliente::ALGORITMO);
});

test('a chave de teste atende ao tamanho mínimo exigido pelo contrato', function () {
    $detalhes = openssl_pkey_get_details(openssl_pkey_get_public(GeradorTokenTeste::chavePublicaPem()));

    expect($detalhes['bits'])->toBeGreaterThanOrEqual(ContratoTokenCliente::TAMANHO_MINIMO_CHAVE_BITS);
});

test('o exemplo válido traz o header que o contrato exige', function () {
    $header = json_decode(
        GeradorTokenTeste::base64UrlDecode(explode('.', GeradorTokenTeste::valido())[0]),
        true,
    );

    expect($header['alg'])->toBe(ContratoTokenCliente::ALGORITMO)
        ->and($header['typ'])->toBe('JWT')
        ->and($header['kid'])->toBe(GeradorTokenTeste::KID);
});

test('o exemplo válido traz todas as claims obrigatórias preenchidas', function () {
    $claims = GeradorTokenTeste::claimsValidas();

    foreach (ClaimTokenCliente::obrigatorias() as $claim) {
        expect($claims)->toHaveKey($claim->value)
            ->and($claims[$claim->value])->not->toBeEmpty();
    }
});

test('o exemplo válido respeita audiência, validade e vocabulário de scope', function () {
    $claims = GeradorTokenTeste::claimsValidas();

    expect($claims['aud'])->toBe(ContratoTokenCliente::AUDIENCE)
        ->and($claims['exp'])->toBeGreaterThan(time())
        ->and($claims['iat'])->toBeLessThanOrEqual(time())
        ->and($claims['exp'] - $claims['iat'])->toBeLessThanOrEqual(ContratoTokenCliente::TTL_MAXIMO_SEGUNDOS)
        ->and(explode(' ', $claims['scope']))->each->toBeIn(ContratoTokenCliente::scopesConhecidos());
});

test('a assinatura do exemplo válido confere com a chave publicada no jwks', function () {
    expect(motivosDeInvalidez(GeradorTokenTeste::valido()))->toBe([]);
});

test('cada exemplo sem claim obrigatória viola apenas a ausência daquela claim', function (ClaimTokenCliente $claim) {
    expect(motivosDeInvalidez(GeradorTokenTeste::semClaim($claim)))->toBe(['claim_ausente:'.$claim->value]);
})->with(ClaimTokenCliente::obrigatorias());

test('cada exemplo inválido viola exatamente o motivo que se propõe a violar', function (string $metodo, string $motivo) {
    expect(motivosDeInvalidez(GeradorTokenTeste::{$metodo}()))->toBe([$motivo]);
})->with([
    'audiência diferente de chat-service' => ['audienciaErrada', 'audiencia_invalida'],
    'exp no passado' => ['expirado', 'expirado'],
    'exp - iat acima do teto' => ['ttlAcimaDoMaximo', 'ttl_acima_do_maximo'],
    'assinado por chave fora do jwks' => ['assinadoPorOutraChave', 'assinatura_invalida'],
    'header sem kid' => ['semKid', 'kid_ausente'],
    'kid ausente do jwks' => ['kidDesconhecido', 'kid_nao_encontrado_no_jwks'],
    'typ diferente de JWT' => ['typInvalido', 'typ_invalido'],
    'scope vazio' => ['scopeVazio', 'claim_vazia:scope'],
    'exp como string' => ['expComoString', 'claim_tipo_invalido:exp'],
    'aud como array' => ['audComoArray', 'claim_tipo_invalido:aud'],
    'iat no futuro' => ['iatNoFuturo', 'iat_no_futuro'],
    'role fora do vocabulário' => ['roleNaoReconhecida', 'role_nao_aceita'],
    'role atendente antes de CHAT-005B' => ['rolePapelAtendente', 'role_nao_aceita'],
    'alg none' => ['algNone', 'algoritmo_nao_suportado'],
    'alg HS256 com a chave pública como segredo' => ['algHs256ComChavePublicaComoSegredo', 'algoritmo_nao_suportado'],
    'token com duas partes' => ['comDuasPartes', 'formato_invalido'],
    'token com cinco partes' => ['comCincoPartes', 'formato_invalido'],
    'payload que não é json' => ['payloadNaoJson', 'formato_invalido'],
]);

test('iat no futuro é rejeitado mesmo com exp - iat dentro do teto', function () {
    $claims = json_decode(
        GeradorTokenTeste::base64UrlDecode(explode('.', GeradorTokenTeste::iatNoFuturo())[1]),
        true,
    );

    expect($claims['exp'] - $claims['iat'])->toBeLessThanOrEqual(ContratoTokenCliente::TTL_MAXIMO_SEGUNDOS)
        ->and(motivosDeInvalidez(GeradorTokenTeste::iatNoFuturo()))->toBe(['iat_no_futuro']);
});

test('role cliente é o único papel aceito enquanto CHAT-005B não existe', function () {
    expect(ContratoTokenCliente::rolesAceitos())->toBe(['cliente'])
        ->and(motivosDeInvalidez(GeradorTokenTeste::valido(['role' => 'cliente'])))->toBe([]);
});

test('o jwt literal publicado no documento confere com a chave versionada', function () {
    $documento = file_get_contents(__DIR__.'/../../docs/contratos/token-cliente.md');

    expect(preg_match('/^(eyJ[\w-]+\.[\w-]+\.[\w-]+)$/m', $documento, $encontrado))->toBe(1);

    [$headerB64, $claimsB64, $assinaturaB64] = explode('.', $encontrado[1]);
    $claims = json_decode(GeradorTokenTeste::base64UrlDecode($claimsB64), true);

    $confere = openssl_verify(
        $headerB64.'.'.$claimsB64,
        GeradorTokenTeste::base64UrlDecode($assinaturaB64),
        GeradorTokenTeste::chavePublicaPem(),
        OPENSSL_ALGO_SHA256,
    );

    expect($confere)->toBe(1)
        ->and($claims['iss'])->toBe(GeradorTokenTeste::SISTEMA_CODIGO)
        ->and($claims['aud'])->toBe(ContratoTokenCliente::AUDIENCE)
        ->and($claims['exp'] - $claims['iat'])->toBe(ContratoTokenCliente::TTL_RECOMENDADO_SEGUNDOS);
});

test('o jwks de chave fraca publica uma chave abaixo do mínimo exigido', function () {
    $jwk = GeradorTokenTeste::jwksChaveFraca()['keys'][0];
    $bits = strlen(GeradorTokenTeste::base64UrlDecode($jwk['n'])) * 8;

    expect($bits)->toBeLessThan(ContratoTokenCliente::TAMANHO_MINIMO_CHAVE_BITS);
});

test('os exemplos de iss não confiável são estruturalmente perfeitos', function (string $token) {
    expect(motivosDeInvalidez($token))->toBe([]);
})->with([
    'sistema não cadastrado' => fn () => GeradorTokenTeste::issSistemaNaoCadastrado(),
    'sistema inativo' => fn () => GeradorTokenTeste::issSistemaInativo('sistema-desativado'),
]);
