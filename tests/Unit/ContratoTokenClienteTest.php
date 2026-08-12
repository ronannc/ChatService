<?php

use App\Enums\ClaimTokenCliente;
use App\Support\ContratoTokenCliente;

test('as claims obrigatórias são exatamente as publicadas no contrato', function () {
    $obrigatorias = array_map(
        fn (ClaimTokenCliente $claim): string => $claim->value,
        ClaimTokenCliente::obrigatorias(),
    );

    expect($obrigatorias)->toBe(['iss', 'sub', 'aud', 'scope', 'exp', 'iat']);
});

test('as claims opcionais são exatamente as reservadas pelo contrato', function () {
    $opcionais = array_map(
        fn (ClaimTokenCliente $claim): string => $claim->value,
        ClaimTokenCliente::opcionais(),
    );

    expect($opcionais)->toBe(['role', 'cliente_unificado_ref', 'jti']);
});

test('exp e iat são numéricas e as demais claims são strings', function () {
    expect(ClaimTokenCliente::Exp->tipo())->toBe('int')
        ->and(ClaimTokenCliente::Iat->tipo())->toBe('int')
        ->and(ClaimTokenCliente::Iss->tipo())->toBe('string')
        ->and(ClaimTokenCliente::Sub->tipo())->toBe('string')
        ->and(ClaimTokenCliente::Aud->tipo())->toBe('string')
        ->and(ClaimTokenCliente::Scope->tipo())->toBe('string');
});

test('role é reconhecida pelo contrato sem ser obrigatória', function () {
    expect(ClaimTokenCliente::Role->obrigatoria())->toBeFalse()
        ->and(ClaimTokenCliente::opcionais())->toContain(ClaimTokenCliente::Role);
});

test('cada claim se declara obrigatória de forma consistente com a lista', function (ClaimTokenCliente $claim) {
    expect($claim->obrigatoria())->toBe(in_array($claim, ClaimTokenCliente::obrigatorias(), true));
})->with(ClaimTokenCliente::cases());

test('a audiência, o algoritmo e o typ são os valores fixos do contrato', function () {
    expect(ContratoTokenCliente::AUDIENCE)->toBe('chat-service')
        ->and(ContratoTokenCliente::ALGORITMO)->toBe('RS256')
        ->and(ContratoTokenCliente::TIPO_HEADER)->toBe('JWT');
});

test('o teto de vida do token é de 15 minutos', function () {
    expect(ContratoTokenCliente::TTL_MAXIMO_SEGUNDOS)->toBe(900)
        ->and(ContratoTokenCliente::TTL_RECOMENDADO_SEGUNDOS)
        ->toBeLessThanOrEqual(ContratoTokenCliente::TTL_MAXIMO_SEGUNDOS);
});

test('o vocabulário de scope é o publicado no contrato', function () {
    expect(ContratoTokenCliente::scopesConhecidos())->toBe(['chat:ler', 'chat:escrever']);
});

test('o contrato reserva o papel de atendente para CHAT-005B sem aceitá-lo hoje', function () {
    expect(ContratoTokenCliente::rolesConhecidos())->toBe(['cliente', 'atendente'])
        ->and(ContratoTokenCliente::rolesAceitos())->toBe(['cliente'])
        ->and(ContratoTokenCliente::rolesAceitos())->not->toContain(ContratoTokenCliente::ROLE_ATENDENTE);
});

test('o cache do jwks tem ttl positivo e negativo definidos, com o negativo mais curto', function () {
    expect(ContratoTokenCliente::TTL_CACHE_JWKS_SEGUNDOS)->toBeGreaterThan(0)
        ->and(ContratoTokenCliente::TTL_CACHE_NEGATIVO_JWKS_SEGUNDOS)->toBeGreaterThan(0)
        ->and(ContratoTokenCliente::TTL_CACHE_NEGATIVO_JWKS_SEGUNDOS)
        ->toBeLessThan(ContratoTokenCliente::TTL_CACHE_JWKS_SEGUNDOS);
});

test('o refetch por kid desconhecido tem intervalo mínimo definido', function () {
    expect(ContratoTokenCliente::INTERVALO_MINIMO_REFETCH_JWKS_SEGUNDOS)->toBeGreaterThanOrEqual(60);
});

test('a busca do jwks tem timeout curto e teto de resposta', function () {
    expect(ContratoTokenCliente::TIMEOUT_CONEXAO_JWKS_SEGUNDOS)
        ->toBeLessThanOrEqual(ContratoTokenCliente::TIMEOUT_TOTAL_JWKS_SEGUNDOS)
        ->and(ContratoTokenCliente::TIMEOUT_TOTAL_JWKS_SEGUNDOS)->toBeLessThanOrEqual(10)
        ->and(ContratoTokenCliente::TAMANHO_MAXIMO_JWKS_BYTES)->toBeGreaterThan(0)
        ->and(ContratoTokenCliente::MAXIMO_CHAVES_JWKS)->toBeGreaterThan(0);
});

test('o contrato exige chave RSA de no mínimo 2048 bits', function () {
    expect(ContratoTokenCliente::TAMANHO_MINIMO_CHAVE_BITS)->toBe(2048);
});

test('o caminho padrão do jwks é relativo, para compor com a jwks_url do sistema', function () {
    expect(ContratoTokenCliente::CAMINHO_PADRAO_JWKS)->toBe('/.well-known/jwks.json')
        ->and(ContratoTokenCliente::CAMINHO_PADRAO_JWKS)->toStartWith('/');
});
