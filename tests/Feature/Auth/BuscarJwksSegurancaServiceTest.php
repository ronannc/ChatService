<?php

use App\Services\Auth\BuscarJwksSegurancaService;
use App\Support\ContratoTokenCliente;
use App\Support\GuardaHostSeguro;
use Illuminate\Support\Facades\Http;
use Tests\Support\GeradorTokenTeste;

const JWKS_URL_TESTE = 'https://gestaodeoficinas.example.com/.well-known/jwks.json';

function buscadorJwksComHostPublico(): BuscarJwksSegurancaService
{
    return new BuscarJwksSegurancaService(
        new GuardaHostSeguro(fn (string $host): array => ['8.8.8.8']),
    );
}

test('busca e devolve o jwks quando a resposta é válida', function () {
    Http::fake([
        JWKS_URL_TESTE => Http::response(GeradorTokenTeste::jwks(), 200, ['Content-Type' => 'application/json']),
    ]);

    $jwks = buscadorJwksComHostPublico()->buscar(JWKS_URL_TESTE);

    expect($jwks['keys'])->toHaveCount(1)
        ->and($jwks['keys'][0]['kid'])->toBe(GeradorTokenTeste::KID);
});

test('recusa url que não é https', function () {
    expect(fn () => buscadorJwksComHostPublico()->buscar('http://gestaodeoficinas.example.com/jwks.json'))
        ->toThrow(RuntimeException::class);
});

test('descarta chaves além do limite em vez de rejeitar a resposta', function () {
    $chaves = collect(range(1, ContratoTokenCliente::MAXIMO_CHAVES_JWKS + 5))
        ->map(fn (int $i): array => [...GeradorTokenTeste::jwks()['keys'][0], 'kid' => "chave-{$i}"])
        ->all();

    Http::fake([
        JWKS_URL_TESTE => Http::response(['keys' => $chaves], 200),
    ]);

    $jwks = buscadorJwksComHostPublico()->buscar(JWKS_URL_TESTE);

    expect($jwks['keys'])->toHaveCount(ContratoTokenCliente::MAXIMO_CHAVES_JWKS);
});

test('rejeita resposta maior que o teto de bytes permitido', function () {
    $corpoGigante = json_encode([
        'keys' => [[...GeradorTokenTeste::jwks()['keys'][0], 'comentario' => str_repeat('a', ContratoTokenCliente::TAMANHO_MAXIMO_JWKS_BYTES)]],
    ]);

    Http::fake([
        JWKS_URL_TESTE => Http::response($corpoGigante, 200),
    ]);

    expect(fn () => buscadorJwksComHostPublico()->buscar(JWKS_URL_TESTE))->toThrow(RuntimeException::class);
});

test('rejeita resposta que não é um jwks válido', function () {
    Http::fake([
        JWKS_URL_TESTE => Http::response('não é json', 200),
    ]);

    expect(fn () => buscadorJwksComHostPublico()->buscar(JWKS_URL_TESTE))->toThrow(RuntimeException::class);
});

test('propaga falha quando o servidor do emissor responde com erro', function () {
    Http::fake([
        JWKS_URL_TESTE => Http::response('', 500),
    ]);

    expect(fn () => buscadorJwksComHostPublico()->buscar(JWKS_URL_TESTE))->toThrow(RuntimeException::class);
});

test('segue um redirect https revalidando o host de destino', function () {
    Http::fake([
        JWKS_URL_TESTE => Http::response('', 302, ['Location' => 'https://cdn.gestaodeoficinas.example.com/jwks.json']),
        'https://cdn.gestaodeoficinas.example.com/jwks.json' => Http::response(GeradorTokenTeste::jwks(), 200),
    ]);

    $jwks = buscadorJwksComHostPublico()->buscar(JWKS_URL_TESTE);

    expect($jwks['keys'][0]['kid'])->toBe(GeradorTokenTeste::KID);
});

test('recusa host de redirect que resolve para ip privado', function () {
    $guarda = new GuardaHostSeguro(function (string $host): array {
        return $host === 'interno.example.com' ? ['10.0.0.5'] : ['8.8.8.8'];
    });

    $buscador = new BuscarJwksSegurancaService($guarda);

    Http::fake([
        JWKS_URL_TESTE => Http::response('', 302, ['Location' => 'https://interno.example.com/jwks.json']),
    ]);

    expect(fn () => $buscador->buscar(JWKS_URL_TESTE))->toThrow(RuntimeException::class);
});

test('recusa excesso de redirects', function () {
    Http::fake(function ($request) {
        return Http::response('', 302, ['Location' => $request->url()]);
    });

    expect(fn () => buscadorJwksComHostPublico()->buscar(JWKS_URL_TESTE))->toThrow(RuntimeException::class);
});
