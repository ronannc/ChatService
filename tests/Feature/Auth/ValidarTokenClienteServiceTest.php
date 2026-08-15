<?php

use App\Enums\ClaimTokenCliente;
use App\Enums\StatusSistema;
use App\Exceptions\TokenClienteInvalidoException;
use App\Models\Sistema;
use App\Services\Auth\BuscarJwksSegurancaService;
use App\Services\Auth\RepositorioJwks;
use App\Services\Auth\ValidarTokenClienteService;
use App\Support\CacheSistema;
use App\Support\ContratoTokenCliente;
use App\Support\GuardaHostSeguro;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\Support\GeradorTokenTeste;

/**
 * Cobre, um por vez, os 18 motivos fechados de invalidez da §4 do contrato
 * (docs/contratos/token-cliente.md) mais o caminho feliz. Roda contra um
 * esquema mínimo de `sistemas` em sqlite (ver `prepararTabelaSistemasParaTeste`
 * em tests/Pest.php) em vez de `RefreshDatabase`: as demais tabelas só
 * migram com DDL exclusivo do Postgres (RLS), indisponível fora do
 * container Docker. `make test` roda a suíte inteira contra Postgres real.
 */
beforeEach(function () {
    prepararTabelaSistemasParaTeste();
    Cache::flush();

    $this->sistema = Sistema::factory()->create([
        'codigo' => GeradorTokenTeste::SISTEMA_CODIGO,
        'jwks_url' => 'https://gestaodeoficinas.example.com/.well-known/jwks.json',
        'status' => StatusSistema::Ativo,
    ]);

    Http::fake([
        $this->sistema->jwks_url => Http::response(GeradorTokenTeste::jwks(), 200),
    ]);

    $this->validar = new ValidarTokenClienteService(
        new RepositorioJwks(
            new BuscarJwksSegurancaService(new GuardaHostSeguro(fn (string $host): array => ['8.8.8.8'])),
        ),
    );
});

test('token válido resolve sistema, sub e scope corretamente', function () {
    $resultado = $this->validar->handle(GeradorTokenTeste::valido());

    expect($resultado->iss)->toBe(GeradorTokenTeste::SISTEMA_CODIGO)
        ->and($resultado->sub)->toBe(GeradorTokenTeste::SUB)
        ->and($resultado->scope)->toBe(GeradorTokenTeste::SCOPE);
});

test('token com role=atendente (CHAT-005B) resolve normalmente e marca ehAtendente()', function () {
    $resultado = $this->validar->handle(GeradorTokenTeste::papelAtendente());

    expect($resultado->role)->toBe(ContratoTokenCliente::ROLE_ATENDENTE)
        ->and($resultado->ehAtendente())->toBeTrue();
});

test('token sem a claim role (fallback) resolve como cliente e ehAtendente() é falso', function () {
    $resultado = $this->validar->handle(GeradorTokenTeste::semClaim(ClaimTokenCliente::Role));

    expect($resultado->role)->toBe(ContratoTokenCliente::ROLE_CLIENTE)
        ->and($resultado->ehAtendente())->toBeFalse();
});

test('cada motivo de invalidez observável no próprio token rejeita com a mesma exceção genérica', function (string $metodoOuValor) {
    $token = method_exists(GeradorTokenTeste::class, $metodoOuValor)
        ? GeradorTokenTeste::{$metodoOuValor}()
        : $metodoOuValor;

    expect(fn () => $this->validar->handle($token))->toThrow(TokenClienteInvalidoException::class, 'Token inválido.');
})->with([
    'formato inválido: duas partes' => ['comDuasPartes'],
    'formato inválido: cinco partes' => ['comCincoPartes'],
    'formato inválido: payload não é json' => ['payloadNaoJson'],
    'algoritmo none' => ['algNone'],
    'algoritmo hs256 com a chave pública como segredo' => ['algHs256ComChavePublicaComoSegredo'],
    'typ inválido' => ['typInvalido'],
    'kid ausente' => ['semKid'],
    'audiência inválida' => ['audienciaErrada'],
    'expirado' => ['expirado'],
    'iat no futuro' => ['iatNoFuturo'],
    'ttl acima do teto' => ['ttlAcimaDoMaximo'],
    'role não aceita: valor fora do vocabulário' => ['roleNaoReconhecida'],
    'assinatura inválida' => ['assinadoPorOutraChave'],
    'kid não encontrado no jwks' => ['kidDesconhecido'],
]);

test('cada claim obrigatória ausente rejeita o token', function (ClaimTokenCliente $claim) {
    expect(fn () => $this->validar->handle(GeradorTokenTeste::semClaim($claim)))
        ->toThrow(TokenClienteInvalidoException::class);
})->with(ClaimTokenCliente::obrigatorias());

test('claim obrigatória vazia rejeita o token', function () {
    expect(fn () => $this->validar->handle(GeradorTokenTeste::scopeVazio()))
        ->toThrow(TokenClienteInvalidoException::class);
});

test('claim com tipo inválido rejeita o token', function () {
    expect(fn () => $this->validar->handle(GeradorTokenTeste::expComoString()))
        ->toThrow(TokenClienteInvalidoException::class);
    expect(fn () => $this->validar->handle(GeradorTokenTeste::audComoArray()))
        ->toThrow(TokenClienteInvalidoException::class);
});

test('iss não cadastrado rejeita o token', function () {
    expect(fn () => $this->validar->handle(GeradorTokenTeste::issSistemaNaoCadastrado()))
        ->toThrow(TokenClienteInvalidoException::class);
});

test('sistema inativo rejeita o token', function () {
    $inativo = Sistema::factory()->create([
        'status' => StatusSistema::Inativo,
        'jwks_url' => $this->sistema->jwks_url,
    ]);

    expect(fn () => $this->validar->handle(GeradorTokenTeste::issSistemaInativo($inativo->codigo)))
        ->toThrow(TokenClienteInvalidoException::class);
});

test('desativar o sistema derruba tokens já emitidos imediatamente, sem esperar o cache', function () {
    // Primeira validação: cadastra o sistema no cache (Ativo).
    $this->validar->handle(GeradorTokenTeste::valido());

    $this->sistema->update(['status' => StatusSistema::Inativo]);
    CacheSistema::esquecer($this->sistema->codigo);

    expect(fn () => $this->validar->handle(GeradorTokenTeste::valido()))
        ->toThrow(TokenClienteInvalidoException::class);
});

test('chave abaixo do mínimo exigido rejeita o token', function () {
    $sistemaChaveFraca = Sistema::factory()->create([
        'jwks_url' => 'https://chave-fraca.example.com/.well-known/jwks.json',
        'status' => StatusSistema::Ativo,
    ]);

    Http::fake([
        $sistemaChaveFraca->jwks_url => Http::response(GeradorTokenTeste::jwksChaveFraca(), 200),
    ]);

    $claims = GeradorTokenTeste::claimsValidas([ClaimTokenCliente::Iss->value => $sistemaChaveFraca->codigo]);
    $header = ['alg' => ContratoTokenCliente::ALGORITMO, 'typ' => 'JWT', 'kid' => 'chat-service-teste-chave-fraca'];

    $conteudo = GeradorTokenTeste::base64UrlEncode(json_encode($header)).'.'.GeradorTokenTeste::base64UrlEncode(json_encode($claims));
    openssl_sign($conteudo, $assinatura, file_get_contents(__DIR__.'/../../Fixtures/Token/chave-privada-fraca-teste.pem'), OPENSSL_ALGO_SHA256);
    $token = $conteudo.'.'.GeradorTokenTeste::base64UrlEncode($assinatura);

    expect(fn () => $this->validar->handle($token))->toThrow(TokenClienteInvalidoException::class);
});

test('jwks inacessível rejeita o token', function () {
    $sistemaJwksFora = Sistema::factory()->create([
        'jwks_url' => 'https://jwks-fora-do-ar.example.com/.well-known/jwks.json',
        'status' => StatusSistema::Ativo,
    ]);

    Http::fake([
        $sistemaJwksFora->jwks_url => Http::response('', 500),
    ]);

    $token = GeradorTokenTeste::valido([ClaimTokenCliente::Iss->value => $sistemaJwksFora->codigo]);

    expect(fn () => $this->validar->handle($token))->toThrow(TokenClienteInvalidoException::class);
});

test('todo motivo de rejeição é logado com o motivo específico, mas a exceção nunca o expõe', function () {
    Log::spy();

    try {
        $this->validar->handle(GeradorTokenTeste::audienciaErrada());
    } catch (TokenClienteInvalidoException $e) {
        expect($e->motivo)->toBe('audiencia_invalida')
            ->and($e->getMessage())->toBe('Token inválido.');
    }

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $mensagem, array $contexto): bool => $contexto['motivo'] === 'audiencia_invalida')
        ->once();
});
