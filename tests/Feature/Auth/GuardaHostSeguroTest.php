<?php

use App\Support\GuardaHostSeguro;

test('aceita um host cujo ip literal é público', function () {
    expect((new GuardaHostSeguro)->ipPublicoDoHost('8.8.8.8'))->toBe('8.8.8.8');
});

test('recusa um host cujo ip literal é privado', function () {
    expect(fn () => (new GuardaHostSeguro)->ipPublicoDoHost('10.0.0.5'))
        ->toThrow(RuntimeException::class);
});

test('recusa loopback', function () {
    expect(fn () => (new GuardaHostSeguro)->ipPublicoDoHost('127.0.0.1'))
        ->toThrow(RuntimeException::class);
});

test('recusa o endpoint de metadados de cloud', function () {
    expect(fn () => (new GuardaHostSeguro)->ipPublicoDoHost('169.254.169.254'))
        ->toThrow(RuntimeException::class);
});

test('recusa faixas privadas rfc1918 e link-local resolvidas via dns', function (string $ip) {
    $guarda = new GuardaHostSeguro(fn (string $host): array => [$ip]);

    expect(fn () => $guarda->ipPublicoDoHost('interno.exemplo.test'))->toThrow(RuntimeException::class);
})->with([
    '10.0.0.1',
    '172.16.5.4',
    '192.168.1.1',
    '169.254.1.1',
    '127.0.0.1',
]);

test('aceita um host que resolve, via resolvedor injetado, para um ip público', function () {
    $guarda = new GuardaHostSeguro(fn (string $host): array => ['8.8.8.8']);

    expect($guarda->ipPublicoDoHost('gestaodeoficinas.example.com'))->toBe('8.8.8.8');
});

test('lança quando o host não resolve para nenhum ip', function () {
    $guarda = new GuardaHostSeguro(fn (string $host): array => []);

    expect(fn () => $guarda->ipPublicoDoHost('nao-resolve.example.com'))->toThrow(RuntimeException::class);
});
