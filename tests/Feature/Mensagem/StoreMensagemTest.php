<?php

use App\Enums\RemetenteMensagem;
use App\Enums\StatusChamado;
use App\Events\MensagemEnviada;
use App\Models\Atendente;
use App\Models\Chamado;
use App\Models\Mensagem;
use App\Models\Sistema;
use App\Services\Auth\BuscarJwksSegurancaService;
use App\Services\Auth\RepositorioJwks;
use App\Services\Auth\ValidarTokenClienteService;
use App\Support\GuardaHostSeguro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\Support\GeradorTokenTeste;

/**
 * Exercita POST /api/chamados/{chamado}/mensagens (CHAT-009): rota dual-auth
 * compartilhada por cliente final (JWT) e atendente interno (Sanctum), guard
 * de escrita (`EnsureAutorizadoEnviarMensagem`) e efeito colateral de
 * alternância de status (`StoreMensagemService`).
 *
 * Depende de Postgres real (RLS/global scope de Chamado/Mensagem/Atendente),
 * roda via `make test`.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    $this->sistema = Sistema::factory()->create();

    Http::fake([
        $this->sistema->jwks_url => Http::response(GeradorTokenTeste::jwks(), 200),
    ]);

    $this->app->bind(ValidarTokenClienteService::class, fn () => new ValidarTokenClienteService(
        new RepositorioJwks(
            new BuscarJwksSegurancaService(new GuardaHostSeguro(fn (string $host): array => ['8.8.8.8'])),
        ),
    ));

    sistemaContext()->set($this->sistema->codigo);

    $this->atendente = Atendente::factory()->create([
        'sistema_id' => $this->sistema->codigo,
        'email' => 'ana@chatservice.test',
        'senha' => Hash::make('password'),
    ]);

    $this->chamado = Chamado::factory()->create([
        'sistema_id' => $this->sistema->codigo,
        'cliente_ref' => GeradorTokenTeste::SUB,
        'atendente_atual_id' => $this->atendente->id,
        'status' => StatusChamado::AguardandoFila,
    ]);
});

test('cliente dono do chamado envia mensagem e o status muda para em_atendimento', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistema->codigo]);

    $resposta = $this->postJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'texto' => 'Olá, preciso de ajuda.',
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertCreated();

    sistemaContext()->set($this->sistema->codigo);

    $mensagem = Mensagem::first();
    expect($mensagem)->not->toBeNull();
    expect($mensagem->texto)->toBe('Olá, preciso de ajuda.');
    expect($mensagem->remetente_tipo)->toBe(RemetenteMensagem::Cliente);
    expect($mensagem->remetente_ref)->toBe(GeradorTokenTeste::SUB);

    expect($this->chamado->fresh()->status)->toBe(StatusChamado::EmAtendimento);
});

test('atendente responsável envia mensagem e o status muda para aguardando_cliente', function () {
    $this->chamado->update(['status' => StatusChamado::EmAtendimento]);

    $token = tokenAtendente($this->atendente);

    $resposta = $this->postJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'texto' => 'Já estou vendo seu caso.',
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertCreated();

    sistemaContext()->set($this->sistema->codigo);

    $mensagem = Mensagem::first();
    expect($mensagem->remetente_tipo)->toBe(RemetenteMensagem::Atendente);
    expect($mensagem->remetente_ref)->toBe((string) $this->atendente->id);

    expect($this->chamado->fresh()->status)->toBe(StatusChamado::AguardandoCliente);
});

test('atendente enviando mensagem quando o chamado já está aguardando_cliente não gera efeito colateral extra', function () {
    $this->chamado->update(['status' => StatusChamado::AguardandoCliente]);

    $token = tokenAtendente($this->atendente);

    $resposta = $this->postJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'texto' => 'Só um complemento.',
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertCreated();

    sistemaContext()->set($this->sistema->codigo);
    expect($this->chamado->fresh()->status)->toBe(StatusChamado::AguardandoCliente);
    expect($this->chamado->fresh()->updated_at->equalTo($this->chamado->updated_at))->toBeTrue();
});

test('cliente que não é dono do chamado recebe 403 e nenhuma mensagem é criada', function () {
    $token = GeradorTokenTeste::valido([
        'iss' => $this->sistema->codigo,
        'sub' => 'outro-cliente',
    ]);

    $resposta = $this->postJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'texto' => 'Tentando ler chamado alheio.',
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertForbidden();

    sistemaContext()->set($this->sistema->codigo);
    expect(Mensagem::count())->toBe(0);
    expect($this->chamado->fresh()->status)->toBe(StatusChamado::AguardandoFila);
});

test('atendente que não é o responsável pelo chamado recebe 403', function () {
    sistemaContext()->set($this->sistema->codigo);
    $outroAtendente = Atendente::factory()->create([
        'sistema_id' => $this->sistema->codigo,
        'email' => 'outro@chatservice.test',
        'senha' => Hash::make('password'),
    ]);

    $token = tokenAtendente($outroAtendente);

    $resposta = $this->postJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'texto' => 'Não sou o responsável.',
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertForbidden();

    sistemaContext()->set($this->sistema->codigo);
    expect(Mensagem::count())->toBe(0);
});

test('cliente dono recebe 403 ao enviar mensagem em chamado resolvido ou finalizado', function (StatusChamado $status) {
    $this->chamado->update(['status' => $status]);

    $token = GeradorTokenTeste::valido(['iss' => $this->sistema->codigo]);

    $resposta = $this->postJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'texto' => 'Chamado já encerrado.',
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertForbidden();

    sistemaContext()->set($this->sistema->codigo);
    expect(Mensagem::count())->toBe(0);
})->with([
    'resolvido' => StatusChamado::Resolvido,
    'finalizado' => StatusChamado::Finalizado,
]);

test('sistema_id da mensagem sempre herda do chamado, nunca de input do body', function () {
    $outroSistema = Sistema::factory()->create();

    $token = GeradorTokenTeste::valido(['iss' => $this->sistema->codigo]);

    $resposta = $this->postJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'texto' => 'Tentando forjar sistema_id.',
        'sistema_id' => $outroSistema->codigo,
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertCreated();
    $resposta->assertJsonPath('sistema_id', $this->sistema->codigo);

    sistemaContext()->set($this->sistema->codigo);
    expect(Mensagem::first()->sistema_id)->toBe($this->sistema->codigo);
});

test('a mensagem é persistida mesmo com o broadcast fake, e o evento é despachado com o payload esperado', function () {
    Event::fake([MensagemEnviada::class]);

    $token = GeradorTokenTeste::valido(['iss' => $this->sistema->codigo]);

    $resposta = $this->postJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'texto' => 'Mensagem com evento fake.',
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertCreated();

    sistemaContext()->set($this->sistema->codigo);
    $mensagem = Mensagem::first();
    expect($mensagem)->not->toBeNull();
    expect($mensagem->texto)->toBe('Mensagem com evento fake.');

    Event::assertDispatched(MensagemEnviada::class, function (MensagemEnviada $event) use ($mensagem) {
        return $event->mensagem->is($mensagem)
            && $event->chamado->is($this->chamado)
            && $event->broadcastWith()['texto'] === 'Mensagem com evento fake.'
            && $event->broadcastWith()['remetente_tipo'] === RemetenteMensagem::Cliente->value;
    });
});

test('token de cliente com claim role=atendente ainda é tratado como cliente e cai em 403 se cliente_ref não bate', function () {
    // O token tem 3 segmentos (formato JWT), então `IdentificarClienteMensagem`
    // sempre tenta validá-lo como cliente primeiro — a claim `role=atendente`
    // não muda esse comportamento aqui (só importa pro fluxo de atendente
    // externo, CHAT-005B). Não deve ser tratado como atendente autorizado por
    // engano: o `sub` do token não bate com o dono do chamado, então cai em
    // 403 pela checagem de `cliente_ref`, igual a qualquer outro cliente.
    $token = GeradorTokenTeste::papelAtendente([
        'iss' => $this->sistema->codigo,
        'sub' => 'outro-cliente-diferente-do-dono',
    ]);

    $resposta = $this->postJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'texto' => 'Tentando se passar por atendente via JWT.',
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertForbidden();

    sistemaContext()->set($this->sistema->codigo);
    expect(Mensagem::count())->toBe(0);
});

test('sequência cliente -> atendente -> cliente alterna o status corretamente a cada mensagem', function () {
    $tokenCliente = GeradorTokenTeste::valido(['iss' => $this->sistema->codigo]);
    $tokenAtendente = tokenAtendente($this->atendente);

    $this->postJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'texto' => 'Mensagem 1 do cliente.',
    ], ['Authorization' => "Bearer {$tokenCliente}"])->assertCreated();

    sistemaContext()->set($this->sistema->codigo);
    expect($this->chamado->fresh()->status)->toBe(StatusChamado::EmAtendimento);

    $this->app['auth']->forgetGuards();

    $this->postJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'texto' => 'Mensagem 1 do atendente.',
    ], ['Authorization' => "Bearer {$tokenAtendente}"])->assertCreated();

    sistemaContext()->set($this->sistema->codigo);
    expect($this->chamado->fresh()->status)->toBe(StatusChamado::AguardandoCliente);

    $this->app['auth']->forgetGuards();

    $this->postJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'texto' => 'Mensagem 2 do cliente.',
    ], ['Authorization' => "Bearer {$tokenCliente}"])->assertCreated();

    sistemaContext()->set($this->sistema->codigo);
    expect($this->chamado->fresh()->status)->toBe(StatusChamado::EmAtendimento);

    expect(Mensagem::count())->toBe(3);
});

test('requisição sem nenhuma autenticação recebe 403 e não cria mensagem', function () {
    $resposta = $this->postJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'texto' => 'Sem token nenhum.',
    ]);

    $resposta->assertForbidden();

    sistemaContext()->set($this->sistema->codigo);
    expect(Mensagem::count())->toBe(0);
});
