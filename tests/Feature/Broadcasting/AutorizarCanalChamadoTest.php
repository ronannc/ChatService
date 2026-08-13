<?php

use App\Models\AtendenteSistema;
use App\Models\Chamado;
use App\Models\Sistema;
use App\Services\Auth\BuscarJwksSegurancaService;
use App\Services\Auth\RepositorioJwks;
use App\Services\Auth\ValidarTokenClienteService;
use App\Services\Broadcasting\AutorizarCanalChamadoService;
use App\Support\GuardaHostSeguro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\GeradorTokenTeste;

/**
 * Exercita POST /api/broadcasting/auth ponta a ponta (App\Services\
 * Broadcasting\AutorizarCanalChamadoService + routes/channels.php),
 * cobrindo os dois mecanismos de auth aceitos (Sanctum pro atendente, JWT do
 * contrato token-cliente pro cliente final).
 *
 * Depende de Postgres real (RLS/FORCE ROW LEVEL SECURITY não existem em
 * sqlite) — roda via `make test`, nunca fora do container.
 *
 * O `codigo`/`iss` usado aqui é sempre gerado pela factory de Sistema (não
 * `GeradorTokenTeste::SISTEMA_CODIGO`, que é o `gestao-oficinas` fixo
 * reaproveitado pelos testes de CHAT-005): este arquivo usa RefreshDatabase
 * (transação por teste), mas os testes de validação de token
 * (EnsureValidTokenClienteTest/ValidarTokenClienteServiceTest) usam o
 * esquema mínimo sem transação (`prepararTabelaSistemasParaTeste`) e podem
 * deixar uma linha `gestao-oficinas` committed na mesma base — rodar a
 * suíte inteira com o código fixo colidiria (unique constraint) dependendo
 * da ordem de execução dos arquivos.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    $this->sistemaCliente = Sistema::factory()->create();

    Http::fake([
        $this->sistemaCliente->jwks_url => Http::response(GeradorTokenTeste::jwks(), 200),
    ]);

    // Mesmo truque de EnsureValidTokenClienteTest: evita depender de DNS/rede
    // real na resolução do host do JWKS.
    $this->app->bind(ValidarTokenClienteService::class, fn () => new ValidarTokenClienteService(
        new RepositorioJwks(
            new BuscarJwksSegurancaService(new GuardaHostSeguro(fn (string $host): array => ['8.8.8.8'])),
        ),
    ));

    // phpunit.xml fixa BROADCAST_CONNECTION=null pra suíte em geral (o
    // NullBroadcaster não exerceria a autorização de canal de verdade) —
    // aqui, especificamente, precisamos do broadcaster real (protocolo
    // Pusher, que o Reverb reaproveita) pra `Broadcast::auth()` de fato
    // assinar/rejeitar. A assinatura é só cálculo local (HMAC), sem
    // dependência de rede ou do servidor Reverb rodando.
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'teste-key',
        'broadcasting.connections.reverb.secret' => 'teste-secret',
        'broadcasting.connections.reverb.app_id' => 'teste-app-id',
    ]);

    // routes/channels.php já rodou no boot da aplicação (antes deste
    // beforeEach), com o BROADCAST_CONNECTION fixado em `null` pelo
    // phpunit.xml — o canal ficou registrado no NullBroadcaster, não no
    // broadcaster `reverb` que acabamos de tornar default. Sem re-registrar
    // aqui, `Broadcast::auth()` resolveria um driver `reverb` "vazio" (sem
    // nenhum canal) e cairia sempre no 403 genérico de "canal desconhecido",
    // mascarando qualquer resultado real da autorização.
    Broadcast::channel('chamado.{chamadoId}', function ($principal, string $chamadoId) {
        return app(AutorizarCanalChamadoService::class)->handle($principal, $chamadoId);
    }, ['guards' => ['sanctum', 'cliente-broadcast']]);
});

test('cliente autorizado consegue assinar o canal do próprio chamado', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);

    sistemaContext()->set($this->sistemaCliente->codigo);
    $chamado = Chamado::factory()
        ->comClienteRef(GeradorTokenTeste::SUB)
        ->create(['sistema_id' => $this->sistemaCliente->codigo]);

    autorizarCanalDoChamado($chamado, $token)
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

test('cliente não é autorizado a assinar o canal de um chamado de outro cliente no mesmo sistema', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);

    sistemaContext()->set($this->sistemaCliente->codigo);
    $chamado = Chamado::factory()
        ->comClienteRef('outro-cliente-que-nao-o-do-token')
        ->create(['sistema_id' => $this->sistemaCliente->codigo]);

    autorizarCanalDoChamado($chamado, $token)->assertForbidden();
});

test('cliente não é autorizado a assinar o canal de um chamado de outro sistema, mesmo com o mesmo sub', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);
    $outroSistema = Sistema::factory()->create();

    sistemaContext()->set($outroSistema->codigo);
    $chamado = Chamado::factory()
        ->comClienteRef(GeradorTokenTeste::SUB)
        ->create(['sistema_id' => $outroSistema->codigo]);

    autorizarCanalDoChamado($chamado, $token)->assertForbidden();
});

test('atendente autorizado consegue assinar o canal de um chamado de um sistema além do seu sistema-base', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaPermitido = Sistema::factory()->create();

    AtendenteSistema::factory()->create([
        'atendente_id' => $atendente->id,
        'sistema_id' => $sistemaPermitido->codigo,
    ]);

    $token = $this->postJson('/api/atendentes/login', [
        'email' => 'ana@chatservice.test',
        'senha' => 'password',
    ])->json('token');

    sistemaContext()->set($sistemaPermitido->codigo);
    $chamado = Chamado::factory()->create(['sistema_id' => $sistemaPermitido->codigo]);

    autorizarCanalDoChamado($chamado, $token)
        ->assertOk()
        ->assertJsonStructure(['auth']);
});

test('atendente não é autorizado a assinar o canal de um chamado de um sistema fora da sua permissão', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaNaoPermitido = Sistema::factory()->create();

    $token = $this->postJson('/api/atendentes/login', [
        'email' => 'ana@chatservice.test',
        'senha' => 'password',
    ])->json('token');

    sistemaContext()->set($sistemaNaoPermitido->codigo);
    $chamado = Chamado::factory()->create(['sistema_id' => $sistemaNaoPermitido->codigo]);

    autorizarCanalDoChamado($chamado, $token)->assertForbidden();
});

test('requisição sem nenhuma autenticação válida é rejeitada', function () {
    sistemaContext()->set($this->sistemaCliente->codigo);
    $chamado = Chamado::factory()->create(['sistema_id' => $this->sistemaCliente->codigo]);

    autorizarCanalDoChamado($chamado, null)->assertForbidden();
});

test('bearer token que não é nem sanctum válido nem jwt de cliente válido é rejeitado', function () {
    sistemaContext()->set($this->sistemaCliente->codigo);
    $chamado = Chamado::factory()->create(['sistema_id' => $this->sistemaCliente->codigo]);

    autorizarCanalDoChamado($chamado, 'token-que-nao-existe-em-lugar-nenhum')
        ->assertForbidden();
});

test('token de cliente expirado é rejeitado, mesmo sendo estruturalmente válido pro sistema/sub certos', function () {
    // Réplica de GeradorTokenTeste::expirado() com `iss` do sistema deste
    // teste (não o SISTEMA_CODIGO fixo do gerador) — este arquivo usa
    // RefreshDatabase por teste e evita esse código fixo de propósito (ver
    // docblock do arquivo).
    $agora = time();
    $token = GeradorTokenTeste::valido([
        'iss' => $this->sistemaCliente->codigo,
        'iat' => $agora - 700,
        'exp' => $agora - 100,
    ]);

    sistemaContext()->set($this->sistemaCliente->codigo);
    $chamado = Chamado::factory()
        ->comClienteRef(GeradorTokenTeste::SUB)
        ->create(['sistema_id' => $this->sistemaCliente->codigo]);

    autorizarCanalDoChamado($chamado, $token)->assertForbidden();
});

test('cliente não é autorizado quando o chamado não tem cliente_ref (nunca bate com o sub do token)', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);

    sistemaContext()->set($this->sistemaCliente->codigo);
    // Sem ->comClienteRef(): cliente_ref fica null (factory default) — chamado
    // aberto sem vínculo de cliente ainda (ex.: criado só pelo atendente).
    $chamado = Chamado::factory()->create(['sistema_id' => $this->sistemaCliente->codigo]);

    autorizarCanalDoChamado($chamado, $token)->assertForbidden();
});

test('cliente é rejeitado ao tentar assinar o canal de um chamado inexistente', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);

    sistemaContext()->set($this->sistemaCliente->codigo);

    $chamadoInexistente = Chamado::factory()
        ->comClienteRef(GeradorTokenTeste::SUB)
        ->create(['sistema_id' => $this->sistemaCliente->codigo]);
    $idInexistente = $chamadoInexistente->id;
    $chamadoInexistente->delete();

    test()->postJson('/api/broadcasting/auth', [
        'channel_name' => "private-chamado.{$idInexistente}",
        'socket_id' => '1234.5678',
    ], ['Authorization' => "Bearer {$token}"])->assertForbidden();
});

test('atendente é rejeitado ao tentar assinar o canal de um chamado inexistente', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaPermitido = Sistema::factory()->create();

    AtendenteSistema::factory()->create([
        'atendente_id' => $atendente->id,
        'sistema_id' => $sistemaPermitido->codigo,
    ]);

    $token = $this->postJson('/api/atendentes/login', [
        'email' => 'ana@chatservice.test',
        'senha' => 'password',
    ])->json('token');

    sistemaContext()->set($sistemaPermitido->codigo);
    $chamado = Chamado::factory()->create(['sistema_id' => $sistemaPermitido->codigo]);
    $idInexistente = $chamado->id;
    $chamado->delete();

    test()->postJson('/api/broadcasting/auth', [
        'channel_name' => "private-chamado.{$idInexistente}",
        'socket_id' => '1234.5678',
    ], ['Authorization' => "Bearer {$token}"])->assertForbidden();
});

test('GUC de sistemas permitidos do atendente não vaza pra consultas seguintes na mesma conexão após autorizar o canal', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaPermitido = Sistema::factory()->create();

    AtendenteSistema::factory()->create([
        'atendente_id' => $atendente->id,
        'sistema_id' => $sistemaPermitido->codigo,
    ]);

    $token = $this->postJson('/api/atendentes/login', [
        'email' => 'ana@chatservice.test',
        'senha' => 'password',
    ])->json('token');

    sistemaContext()->set($sistemaPermitido->codigo);
    $chamado = Chamado::factory()->create(['sistema_id' => $sistemaPermitido->codigo]);

    autorizarCanalDoChamado($chamado, $token)->assertOk();

    $guc = DB::selectOne(
        "select current_setting('app.sistemas_permitidos_atendente', true) as v"
    )->v;

    expect($guc)
        ->toBeEmpty(); // Nada limpa este GUC hoje (só os testes chamam limparSistemasPermitidosAtendente()) —
    // numa conexão reaproveitada entre requests (ex.: worker/Octane/pool), a
    // lista de sistemas permitidos do atendente da última autorização de
    // canal continuaria ativa pra qualquer query seguinte que caia nessa
    // mesma conexão, ampliando a visibilidade de RLS além do previsto.
});
