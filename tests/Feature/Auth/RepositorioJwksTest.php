<?php

use App\Exceptions\TokenClienteInvalidoException;
use App\Models\Sistema;
use App\Services\Auth\BuscarJwksSegurancaService;
use App\Services\Auth\RepositorioJwks;
use App\Support\GuardaHostSeguro;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\GeradorTokenTeste;

beforeEach(function () {
    Cache::flush();

    $this->sistema = Sistema::factory()->make([
        'codigo' => GeradorTokenTeste::SISTEMA_CODIGO,
        'jwks_url' => 'https://gestaodeoficinas.example.com/.well-known/jwks.json',
    ]);

    $this->repositorio = new RepositorioJwks(
        new BuscarJwksSegurancaService(new GuardaHostSeguro(fn (string $host): array => ['8.8.8.8'])),
    );
});

test('busca o jwks uma vez e reaproveita do cache nas chamadas seguintes', function () {
    Http::fake([
        $this->sistema->jwks_url => Http::response(GeradorTokenTeste::jwks(), 200),
    ]);

    $this->repositorio->obterParaKid($this->sistema, GeradorTokenTeste::KID);
    $this->repositorio->obterParaKid($this->sistema, GeradorTokenTeste::KID);
    $this->repositorio->obterParaKid($this->sistema, GeradorTokenTeste::KID);

    Http::assertSentCount(1);
});

test('mantém o cache negativo pelo intervalo configurado após uma falha', function () {
    Http::fake([
        $this->sistema->jwks_url => Http::response('', 500),
    ]);

    expect(fn () => $this->repositorio->obterParaKid($this->sistema, GeradorTokenTeste::KID))
        ->toThrow(TokenClienteInvalidoException::class);
    expect(fn () => $this->repositorio->obterParaKid($this->sistema, GeradorTokenTeste::KID))
        ->toThrow(TokenClienteInvalidoException::class);

    // A segunda chamada não deveria ter martelado o emissor de novo: só uma
    // request HTTP real foi feita, a segunda leu o cache negativo.
    Http::assertSentCount(1);
});

test('refetch por kid desconhecido busca uma vez e passa a enxergar a chave nova', function () {
    $jwksAntigo = GeradorTokenTeste::jwks();
    $jwksComChaveNova = $jwksAntigo;
    $jwksComChaveNova['keys'][] = [...$jwksAntigo['keys'][0], 'kid' => 'chat-service-teste-2027'];

    Http::fakeSequence()
        ->push($jwksAntigo, 200)
        ->push($jwksComChaveNova, 200);

    // Primeira chamada popula o cache com o JWKS antigo (sem o kid novo).
    $this->repositorio->obterParaKid($this->sistema, GeradorTokenTeste::KID);

    $jwks = $this->repositorio->obterParaKid($this->sistema, 'chat-service-teste-2027');

    expect(collect($jwks['keys'])->pluck('kid'))->toContain('chat-service-teste-2027');
    Http::assertSentCount(2);
});

test('não refaz o refetch por kid desconhecido antes do intervalo mínimo', function () {
    Http::fake([
        $this->sistema->jwks_url => Http::response(GeradorTokenTeste::jwks(), 200),
    ]);

    // A 1ª chamada popula o cache (nenhum JWKS ainda existia — não é
    // refetch). A 2ª pede um kid ausente e dispara o único refetch
    // permitido. A 3ª pede o mesmo kid ausente de novo e tem que reaproveitar
    // o que já está em cache, sem martelar o emissor.
    $this->repositorio->obterParaKid($this->sistema, GeradorTokenTeste::KID);
    $this->repositorio->obterParaKid($this->sistema, 'kid-desconhecido');
    $this->repositorio->obterParaKid($this->sistema, 'kid-desconhecido');

    Http::assertSentCount(2);
});
