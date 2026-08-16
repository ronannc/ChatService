# Graph Report - ChatService  (2026-08-16)

## Corpus Check
- 196 files · ~58,566 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 848 nodes · 1428 edges · 78 communities (61 shown, 17 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 28 edges (avg confidence: 0.77)
- Token cost: 0 input · 246,513 output

## Community Hubs (Navigation)
- Controllers HTTP (Sistema/Auth)
- Middlewares de Autorização de Mensagem
- Contrato Token Cliente e Boas Práticas Eloquent
- Composer Dependencies
- Claims do Token Cliente
- Gerador de Token de Teste (JWT)
- Boas Práticas Performance de Banco
- Atendente Externo e Bypass de RLS
- Ambiente Docker e Boas Práticas de Filas
- Enums e Evento MensagemEnviada
- Busca de JWKS e Segurança de Host
- Composer Scripts
- Service Providers (App/Horizon)
- Model User e Configs Base
- Convenção Service-por-Ação e Agentes do Time
- Services de Sistema (CRUD)
- Relacionamentos Atendente-Sistema-Chamado
- Frontend Build (Vite/Tailwind)
- Boas Práticas Events e Notifications
- Repositório de JWKS (cache/lock)
- Boas Práticas Routing e Validation
- Test Helpers e Pest Setup
- Atendente: Login, Provisionamento e Seeders
- Model Atendente e AtendenteContext
- Configuração do Horizon
- Factories de Teste (Chamado/Mensagem/Sistema)
- Model Atendente e Factory
- Migrations de Mensagens e RLS
- SistemaContext (bypass RLS)
- Boas Práticas Error Handling
- Boas Práticas Task Scheduling
- Script plan-task.js
- Migrations Base (Users/Cache)
- Migrations de Chamados (índices/campos)
- AutorizarCanalChamadoService
- SistemaScope (global scope)
- Configuração de Logging
- Configuração Sanctum
- Boas Práticas Índices Compostos
- Console Artisan Base
- Configuração MCP Laravel Boost
- Boas Práticas DI e Interfaces
- Boas Práticas Context Facade
- Boas Práticas Cache Memoization
- Boas Práticas Cache Tags
- Boas Práticas Cursor/Lazy Collections
- Docker Entrypoint Script
- Docker Postgres Init Script
- Integração CI com 4TechLead
- Boas Práticas Concurrency

## God Nodes (most connected - your core abstractions)
1. `GeradorTokenTeste` - 49 edges
2. `Sistema` - 46 edges
3. `Atendente` - 38 edges
4. `Chamado` - 33 edges
5. `ValidarTokenClienteService` - 31 edges
6. `SistemaContext` - 27 edges
7. `RepositorioJwks` - 23 edges
8. `BuscarJwksSegurancaService` - 18 edges
9. `ContratoTokenCliente` - 18 edges
10. `Mensagem` - 17 edges

## Surprising Connections (you probably didn't know these)
- `Use Atomic Locks for Race Conditions` --semantically_similar_to--> `Idempotência via firstOrCreate (não catch de unique)`  [INFERRED] [semantically similar]
  .claude/skills/laravel-best-practices/rules/architecture.md → .ai/rules/atendente-externo.md
- `Single-Purpose Action Classes` --semantically_similar_to--> `Um Service por ação, controller magro`  [INFERRED] [semantically similar]
  .claude/skills/laravel-best-practices/rules/architecture.md → .ai/rules/controllers.md
- `Endpoint JWKS (RFC 7517)` --semantically_similar_to--> `Always Set Explicit Timeouts`  [INFERRED] [semantically similar]
  docs/contratos/token-cliente.md → .claude/skills/laravel-best-practices/rules/http-client.md
- `Cache e Refetch do JWKS (§3.2)` --semantically_similar_to--> `Retry with Backoff for External APIs`  [INFERRED] [semantically similar]
  docs/contratos/token-cliente.md → .claude/skills/laravel-best-practices/rules/http-client.md
- `tokenAtendente()` --references--> `Atendente`  [EXTRACTED]
  tests/Pest.php → app/Models/Atendente.php

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **ChatService agent team roles collaborating on a diff** — claude_agents_dev_laravel_dev_laravel, claude_agents_qa_pest_qa_pest, claude_agents_po_validator_po_validator, claude_agents_perf_specialist_perf_specialist, claude_agents_security_reviewer_security_reviewer [EXTRACTED 1.00]
- **RLS bypass mechanism for cross-system atendente lookup** — ai_rules_atendente_externo_atendenteexternoprovisionamentoservice, ai_rules_atendente_externo_sistemacontext_guc_bypass, ai_rules_atendente_externo_loginatendenteservice_buscarporemailbypassandorls, ai_rules_atendente_externo_risco_savepoint_set_local [EXTRACTED 1.00]
- **Dual-auth mensagem routes sharing one URI across cliente/atendente middlewares** — ai_rules_services_mensagem_identificarclientemensagem, ai_rules_services_mensagem_identificaratendentemensagem, ai_rules_services_mensagem_mensagens_isolamento_sistema_policy, ai_rules_services_mensagem_dual_auth_middleware_pattern [EXTRACTED 1.00]
- **Token Validation Pipeline (contract, code, tests)** — docs_contratos_token_cliente, app_enums_claimtokencliente, app_support_contratotokencliente, tests_support_geradortokenteste [EXTRACTED 1.00]
- **Broadcast Channel Authorization Flow** — docs_contratos_canal_chamado_broadcast, app_services_broadcasting_autorizarcanalchamadoservice, docs_contratos_token_cliente, docker_compose_reverb_service [INFERRED 0.85]
- **Local Dev Environment via Docker Compose** — claude_md_docker_compose_local_env, readme_docker_local_environment, docker_compose, readme_configuration_table [EXTRACTED 1.00]

## Communities (78 total, 17 thin omitted)

### Community 0 - "Controllers HTTP (Sistema/Auth)"
Cohesion: 0.06
Nodes (23): SistemaController, AuthController, MeController, ChamadoController, Controller, MensagemController, StoreSistemaRequest, UpdateSistemaRequest (+15 more)

### Community 1 - "Middlewares de Autorização de Mensagem"
Cohesion: 0.10
Nodes (20): EnableAtendenteAuthRlsBypass, EnsureAdminApiKey, EnsureAutorizadoEnviarMensagem, EnsureParticipanteChamado, EnsureScopeEscreverCliente, EnsureValidTokenCliente, IdentificarAtendenteMensagem, IdentificarClienteMensagem (+12 more)

### Community 2 - "Contrato Token Cliente e Boas Práticas Eloquent"
Cohesion: 0.05
Nodes (48): Configuration Best Practices, App::environment() Checks, Constants and Language Files, Encrypted Env / External Secrets, env() Only in Config Files, Eloquent Best Practices, Define Attribute Casts, Avoid Hardcoded Table Names in Queries (+40 more)

### Community 3 - "Composer Dependencies"
Cohesion: 0.04
Nodes (46): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+38 more)

### Community 4 - "Claims do Token Cliente"
Cohesion: 0.07
Nodes (12): obrigatoria(), opcionais(), TokenClienteInvalidoException, ValidarTokenClienteService, ContratoTokenCliente, Motivos de Invalidez (tabela fechada), Firebase\JWT\JWK, Illuminate\Contracts\Cache\LockTimeoutException (+4 more)

### Community 5 - "Gerador de Token de Teste (JWT)"
Cohesion: 0.08
Nodes (4): ClaimTokenCliente, GeradorTokenTeste, chavePublicaDoHeader(), motivosDeInvalidez()

### Community 6 - "Boas Práticas Performance de Banco"
Cohesion: 0.05
Nodes (37): Laravel Boost Guidelines (embedded), Database Performance Best Practices, Chunk / chunkById Large Datasets, cursor() Memory-Efficient Iteration, Eager Load Relationships (N+1 prevention), Add Database Indexes, No Queries in Blade Templates, Select Only Needed Columns (+29 more)

### Community 7 - "Atendente Externo e Bypass de RLS"
Cohesion: 0.08
Nodes (29): atendente_sistema (tabela de vínculo), AtendenteContext::sistemasPermitidos(), Atendente Externo (CHAT-005B), ProvisionarAtendenteExternoService, Bypass de global scope/RLS só no lookup (mecanismo), EnableAtendenteAuthRlsBypass (middleware), EnsureValidTokenCliente (middleware), Idempotência via firstOrCreate (não catch de unique) (+21 more)

### Community 8 - "Ambiente Docker e Boas Práticas de Filas"
Cohesion: 0.08
Nodes (28): CLAUDE.md Project Guidance, CHAT-003 Postgres Non-Superuser Role for RLS, Docker Compose as Real Local Environment, Queue & Job Best Practices, Bus::batch() Batch Related Jobs, Use Horizon for Complex Queue Scenarios, RateLimited Middleware for Jobs, retry_after Greater Than timeout (+20 more)

### Community 9 - "Enums e Evento MensagemEnviada"
Cohesion: 0.13
Nodes (12): MensagemEnviada, Chamado, Mensagem, Illuminate\Broadcasting\Channel, Illuminate\Broadcasting\InteractsWithSockets, Illuminate\Broadcasting\PrivateChannel, Illuminate\Contracts\Broadcasting\ShouldBroadcast, Illuminate\Database\QueryException (+4 more)

### Community 10 - "Busca de JWKS e Segurança de Host"
Cohesion: 0.19
Nodes (7): BuscarJwksSegurancaService, GuardaHostSeguro, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\Event, Illuminate\Support\Facades\Http, buscadorJwksComHostPublico()

### Community 11 - "Composer Scripts"
Cohesion: 0.08
Nodes (27): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+19 more)

### Community 12 - "Service Providers (App/Horizon)"
Cohesion: 0.09
Nodes (9): AppServiceProvider, HorizonServiceProvider, ClienteAutenticadoBroadcast, Illuminate\Contracts\Auth\Authenticatable, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\Gate, Illuminate\Support\ServiceProvider, Laravel\Horizon\Horizon (+1 more)

### Community 13 - "Model User e Configs Base"
Cohesion: 0.11
Nodes (12): User, static, UserFactory, DatabaseSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Seeder (+4 more)

### Community 14 - "Convenção Service-por-Ação e Agentes do Time"
Cohesion: 0.11
Nodes (23): Um Service por ação, controller magro, StoreSistemaService (exemplo), dev-laravel agent, perf-specialist agent, po-validator agent, qa-pest agent, security-reviewer agent, Posse de arquivo dev x qa (fronteira reforçada por hook) (+15 more)

### Community 15 - "Services de Sistema (CRUD)"
Cohesion: 0.16
Nodes (6): Sistema, UpdateSistemaService, CacheSistema, SistemaSeeder, Laravel\Sanctum\PersonalAccessToken, issDoToken()

### Community 16 - "Relacionamentos Atendente-Sistema-Chamado"
Cohesion: 0.20
Nodes (6): AtendenteSistema, sistema(), AtendenteSistemaFactory, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 17 - "Frontend Build (Vite/Tailwind)"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 18 - "Boas Práticas Events e Notifications"
Cohesion: 0.16
Nodes (14): Events & Notifications Best Practices, afterCommit() on Notifications, Always Queue Notifications (ShouldQueue), Route Notification Channels to Dedicated Queues, Event Discovery / event:cache, HasLocalePreference on Notifiable Models, On-Demand Notifications, ShouldDispatchAfterCommit (+6 more)

### Community 20 - "Boas Práticas Routing e Validation"
Cohesion: 0.17
Nodes (11): Routing & Controllers Best Practices, Implicit Route Model Binding, Use Resource Controllers, Scoped Bindings for Nested Resources, Keep Controllers Thin, Type-Hint Form Requests, Validation & Forms Best Practices, after() Method for Custom Validation (+3 more)

### Community 21 - "Test Helpers e Pest Setup"
Cohesion: 0.20
Nodes (8): Illuminate\Foundation\Testing\TestCase, Illuminate\Testing\TestResponse, autorizarCanalDoChamado(), criarAtendente(), prepararTabelaSistemasParaTeste(), sistemaContext(), tokenAtendente(), TestCase

### Community 22 - "Atendente: Login, Provisionamento e Seeders"
Cohesion: 0.24
Nodes (4): AtendenteSeeder, Claim role (cliente/atendente), Illuminate\Auth\AuthenticationException, Illuminate\Support\Facades\Hash

### Community 23 - "Model Atendente e AtendenteContext"
Cohesion: 0.27
Nodes (3): Atendente, AtendenteContext, Illuminate\Support\Collection

### Community 24 - "Configuração do Horizon"
Cohesion: 0.18
Nodes (11): horizon:snapshot scheduling for metrics, metrics.trim_snapshots (snapshot count, not duration), Horizon::routeMailNotificationsTo / routeSlackNotificationsTo, waits config (LongWaitDetected threshold), balance: false for fixed worker count, balanceCooldown / balanceMaxShift (anti burst scaling), environments merges into defaults (Horizon config), Named supervisors to enforce queue priority (+3 more)

### Community 25 - "Factories de Teste (Chamado/Mensagem/Sistema)"
Cohesion: 0.22
Nodes (5): ChamadoFactory, static, MensagemFactory, SistemaFactory, Illuminate\Database\Eloquent\Factories\Factory

### Community 26 - "Model Atendente e Factory"
Cohesion: 0.20
Nodes (5): AtendenteFactory, static, Illuminate\Auth\Authenticatable, Illuminate\Database\Eloquent\Relations\HasMany, Laravel\Sanctum\HasApiTokens

### Community 29 - "Boas Práticas Error Handling"
Cohesion: 0.29
Nodes (6): Error Handling Best Practices, Add Context to Exception Classes (context()), Exception Reporting and Rendering (co-located vs centralized), Force JSON Error Rendering for API Routes, ShouldntReport Interface, Throttle High-Volume Exceptions

### Community 30 - "Boas Práticas Task Scheduling"
Cohesion: 0.29
Nodes (7): Task Scheduling Best Practices, environments() to Restrict Tasks, Schedule Groups for Shared Configuration, onOneServer() on Multi-Server Deployments, runInBackground() for Concurrent Long Tasks, takeUntilTimeout() for Time-Bounded Processing, withoutOverlapping() on Variable-Duration Tasks

### Community 31 - "Script plan-task.js"
Cohesion: 0.29
Nodes (6): ANGULOS, CONTEXT_SCHEMA, meta, PLAN_SCHEMA, planosValidos, SYNTHESIS_SCHEMA

### Community 35 - "SistemaScope (global scope)"
Cohesion: 0.60
Nodes (3): SistemaScope, Illuminate\Database\Eloquent\Builder, Illuminate\Database\Eloquent\Scope

### Community 36 - "Configuração de Logging"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 37 - "Configuração Sanctum"
Cohesion: 0.40
Nodes (4): Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Foundation\Http\Middleware\ValidateCsrfToken, Laravel\Sanctum\Http\Middleware\AuthenticateSession, Laravel\Sanctum\Sanctum

### Community 38 - "Boas Práticas Índices Compostos"
Cohesion: 0.67
Nodes (3): Compound indexes matching orderBy column order, whereIn + subquery over whereHas, toQuery() for bulk operations on collections

## Ambiguous Edges - Review These
- `Use defer() for Post-Response Work` → `Use Context facade for request-scoped data`  [AMBIGUOUS]
  .claude/skills/laravel-best-practices/rules/architecture.md · relation: conceptually_related_to
- `$attributes->merge() in component templates` → `View Composers for shared view data`  [AMBIGUOUS]
  .claude/skills/laravel-best-practices/rules/blade-views.md · relation: conceptually_related_to
- `Cache Tags for group invalidation` → `Failover cache stores in production`  [AMBIGUOUS]
  .claude/skills/laravel-best-practices/rules/caching.md · relation: conceptually_related_to
- `Higher-Order Messages for simple operations` → `#[CollectedBy] for custom collection classes`  [AMBIGUOUS]
  .claude/skills/laravel-best-practices/rules/collections.md · relation: conceptually_related_to

## Knowledge Gaps
- **129 isolated node(s):** `meta`, `CONTEXT_SCHEMA`, `PLAN_SCHEMA`, `SYNTHESIS_SCHEMA`, `ANGULOS` (+124 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **17 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Use defer() for Post-Response Work` and `Use Context facade for request-scoped data`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **What is the exact relationship between `$attributes->merge() in component templates` and `View Composers for shared view data`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **What is the exact relationship between `Cache Tags for group invalidation` and `Failover cache stores in production`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **What is the exact relationship between `Higher-Order Messages for simple operations` and `#[CollectedBy] for custom collection classes`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **Why does `GeradorTokenTeste` connect `Gerador de Token de Teste (JWT)` to `Busca de JWKS e Segurança de Host`, `Claims do Token Cliente`, `Services de Sistema (CRUD)`?**
  _High betweenness centrality (0.090) - this node is a cross-community bridge._
- **Why does `Sistema` connect `Services de Sistema (CRUD)` to `Controllers HTTP (Sistema/Auth)`, `Claims do Token Cliente`, `Enums e Evento MensagemEnviada`, `Busca de JWKS e Segurança de Host`, `Relacionamentos Atendente-Sistema-Chamado`, `Repositório de JWKS (cache/lock)`, `Test Helpers e Pest Setup`, `Atendente: Login, Provisionamento e Seeders`, `Factories de Teste (Chamado/Mensagem/Sistema)`, `Model Atendente e Factory`?**
  _High betweenness centrality (0.082) - this node is a cross-community bridge._
- **Why does `Pest Testing Skill` connect `Boas Práticas Performance de Banco` to `Claims do Token Cliente`?**
  _High betweenness centrality (0.068) - this node is a cross-community bridge._
