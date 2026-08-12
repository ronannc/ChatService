<?php

use App\Enums\ClaimTokenCliente;
use App\Enums\StatusSistema;
use App\Models\Sistema;
use Database\Seeders\SistemaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\GeradorTokenTeste;

uses(RefreshDatabase::class);

function issDoToken(string $token): string
{
    $claims = json_decode(GeradorTokenTeste::base64UrlDecode(explode('.', $token)[1]), true);

    return $claims[ClaimTokenCliente::Iss->value];
}

test('o iss do exemplo válido corresponde a um sistema cadastrado e ativo', function () {
    $this->seed(SistemaSeeder::class);

    $sistema = Sistema::where('codigo', issDoToken(GeradorTokenTeste::valido()))->first();

    expect($sistema)->not->toBeNull()
        ->and($sistema->status)->toBe(StatusSistema::Ativo);
});

test('o exemplo de sistema não cadastrado não corresponde a nenhum registro', function () {
    $this->seed(SistemaSeeder::class);

    $iss = issDoToken(GeradorTokenTeste::issSistemaNaoCadastrado());

    expect(Sistema::where('codigo', $iss)->exists())->toBeFalse();
});

test('o exemplo de sistema inativo corresponde a um registro existente porém inativo', function () {
    $sistema = Sistema::factory()->create(['status' => StatusSistema::Inativo]);

    $iss = issDoToken(GeradorTokenTeste::issSistemaInativo($sistema->codigo));

    expect(Sistema::where('codigo', $iss)->where('status', StatusSistema::Ativo)->exists())->toBeFalse()
        ->and(Sistema::where('codigo', $iss)->exists())->toBeTrue();
});

test('cada iss resolve para a jwks_url do seu próprio sistema', function () {
    Sistema::factory()->create([
        'codigo' => 'gestao-oficinas',
        'jwks_url' => 'https://gestaodeoficinas.example.com/.well-known/jwks.json',
    ]);

    Sistema::factory()->create([
        'codigo' => 'portal-clientes',
        'jwks_url' => 'https://portalclientes.example.com/.well-known/jwks.json',
    ]);

    $jwksDoIss = fn (string $iss): string => Sistema::where('codigo', $iss)->value('jwks_url');

    expect($jwksDoIss('gestao-oficinas'))->toBe('https://gestaodeoficinas.example.com/.well-known/jwks.json')
        ->and($jwksDoIss('portal-clientes'))->toBe('https://portalclientes.example.com/.well-known/jwks.json')
        ->and($jwksDoIss('gestao-oficinas'))->not->toBe($jwksDoIss('portal-clientes'));
});

test('a factory de sistema produz jwks_url que o cadastro aceitaria', function () {
    $urls = collect(range(1, 20))->map(fn (): string => Sistema::factory()->make()->jwks_url);

    expect($urls)->each->toStartWith('https://');
});
