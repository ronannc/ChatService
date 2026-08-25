# Graph Report - ChatService  (2026-08-25)

## Corpus Check
- 218 files · ~75,218 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 980 nodes · 1643 edges · 104 communities (78 shown, 26 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 35 edges (avg confidence: 0.78)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `060af345`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- BuscarJwksSegurancaService
- Atendente
- SistemaContext
- Queue & Job Best Practices
- composer.json
- Database Performance Best Practices
- ClienteAutenticadoBroadcast
- scripts
- ListarFilaChamadosService.php
- ProvisionarAtendenteExternoService
- Eloquent Best Practices
- User
- GeradorTokenTeste
- ValidarTokenClienteService
- devDependencies
- What You Must Do When Invoked
- Events & Notifications Best Practices
- ContratoTokenCliente
- Illuminate\Foundation\Http\FormRequest
- laravel-best-practices skill
- Chamado
- StoreMensagemTest.php
- Routing & Controllers Best Practices
- Pest.php
- Configuração do Horizon
- graphify reference: extra exports and benchmark
- Illuminate\Database\Migrations\Migration
- HorizonServiceProvider
- Contrato do token assinado — sistema integrado → chat service
- Regras de Error Handling
- Regras de Scheduling
- graphify reference: query, path, explain
- Conventions & Style Best Practices
- logging.php
- sanctum.php
- AtendenteContext
- infer-conventions skill
- Tailwind CSS Development Skill
- plan-task.js
- graphify reference: add a URL and watch a folder
- whereIn + subquery over whereHas
- Illuminate\Support\Facades\Schema
- Illuminate\Database\Schema\Blueprint
- RepositorioJwks
- Sistema
- console.php
- laravel-boost
- SistemaContext
- perf-specialist agent
- graphify trigger section (.claude/CLAUDE.md)
- Code to Interfaces at system boundaries
- Use Context facade for request-scoped data
- Cache::memo() avoid redundant hits per request
- Cache Tags for group invalidation
- cursor() vs lazy() choice
- entrypoint.sh
- init.sh
- Enviar métricas para 4TechLead Workflow
- qa-pest agent
- Use Concurrency::run() for Parallel Execution
- graphify reference: commit hook and native CLAUDE.md integration
- graphify reference: incremental update and cluster-only
- graphify reference: GitHub clone and cross-repo merge
- graphify reference: transcribe video and audio
- extraction-spec.md
- SistemaController.php
- Controller
- LoginAtendenteRequest
- HTTP Client Best Practices
- Security Best Practices
- AvancarFluxoRequest
- .ai/rules/index.md
- Configuration Best Practices
- Illuminate\Support\Str
- AtendenteContext.php

## God Nodes (most connected - your core abstractions)
1. `GeradorTokenTeste` - 49 edges
2. `Sistema` - 48 edges
3. `Chamado` - 47 edges
4. `Atendente` - 40 edges
5. `SistemaContext` - 33 edges
6. `ValidarTokenClienteService` - 31 edges
7. `RepositorioJwks` - 23 edges
8. `TokenClienteValidado` - 19 edges
9. `AtendenteContext` - 19 edges
10. `BuscarJwksSegurancaService` - 18 edges

## Surprising Connections (you probably didn't know these)
- `Single-Purpose Action Classes` --semantically_similar_to--> `Um Service por ação, controller magro`  [INFERRED] [semantically similar]
  .claude/skills/laravel-best-practices/rules/architecture.md → .ai/rules/controllers.md
- `Endpoint JWKS (RFC 7517)` --semantically_similar_to--> `Always Set Explicit Timeouts`  [INFERRED] [semantically similar]
  docs/contratos/token-cliente.md → .claude/skills/laravel-best-practices/rules/http-client.md
- `Cache e Refetch do JWKS (§3.2)` --semantically_similar_to--> `Retry with Backoff for External APIs`  [INFERRED] [semantically similar]
  docs/contratos/token-cliente.md → .claude/skills/laravel-best-practices/rules/http-client.md
- `Risco: policy OR de chamados vaza se os dois GUCs coexistirem sujos` --semantically_similar_to--> `Isolamento por sistema_id (global scope + RLS)`  [INFERRED] [semantically similar]
  .ai/rules/chamado-fila.md → .claude/agents/dev-laravel.md
- `Use Atomic Locks for Race Conditions` --semantically_similar_to--> `Idempotência via firstOrCreate (não catch de unique)`  [INFERRED] [semantically similar]
  .claude/skills/laravel-best-practices/rules/architecture.md → .ai/rules/atendente-externo.md

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Rule files scoped to app code paths via index globs** — ai_rules_index, ai_rules_atendente_externo, ai_rules_controllers, ai_rules_mensagem, ai_rules_services_mensagem, ai_rules_chamado_fila, ai_rules_tokens [EXTRACTED 1.00]
- **ChatService agent team review flow** — claude_agents_dev_laravel, claude_agents_qa_pest, claude_agents_po_validator, claude_agents_perf_specialist, claude_agents_security_reviewer [EXTRACTED 1.00]
- **RLS bypass mechanism for cross-system atendente lookup** — ai_rules_atendente_externo_atendenteexternoprovisionamentoservice, ai_rules_atendente_externo_sistemacontext_guc_bypass, ai_rules_atendente_externo_loginatendenteservice_buscarporemailbypassandorls, ai_rules_atendente_externo_risco_savepoint_set_local [EXTRACTED 1.00]
- **Dual-auth mensagem routes sharing one URI across cliente/atendente middlewares** — ai_rules_services_mensagem_identificarclientemensagem, ai_rules_services_mensagem_identificaratendentemensagem, ai_rules_services_mensagem_mensagens_isolamento_sistema_policy, ai_rules_services_mensagem_dual_auth_middleware_pattern [EXTRACTED 1.00]
- **Token Validation Pipeline (contract, code, tests)** — docs_contratos_token_cliente, app_enums_claimtokencliente, app_support_contratotokencliente, tests_support_geradortokenteste [EXTRACTED 1.00]
- **Chamados dual-GUC sistema_id isolation mechanism** — ai_rules_chamado_fila_guc_current_sistema_id, ai_rules_chamado_fila_guc_sistemas_permitidos_atendente, ai_rules_chamado_fila_chamados_sistemas_permitidos_atendente_policy, app_services_chamado_listarfilachamadosservice [INFERRED 0.85]
- **Broadcast Channel Authorization Flow** — docs_contratos_canal_chamado_broadcast, app_services_broadcasting_autorizarcanalchamadoservice, docs_contratos_token_cliente, docker_compose_reverb_service [INFERRED 0.85]

## Communities (104 total, 26 thin omitted)

### Community 0 - "BuscarJwksSegurancaService"
Cohesion: 0.15
Nodes (8): BuscarJwksSegurancaService, GuardaHostSeguro, Illuminate\Contracts\Cache\LockTimeoutException, Illuminate\Support\Facades\Cache, Illuminate\Support\Facades\Http, Illuminate\Support\Facades\Log, buscadorJwksComHostPublico(), Throwable

### Community 1 - "Atendente"
Cohesion: 0.12
Nodes (14): Atendente, AtendenteSistema, AtendenteFactory, static, AtendenteSistemaFactory, SistemaFactory, AtendenteSeeder, Illuminate\Auth\Authenticatable (+6 more)

### Community 2 - "SistemaContext"
Cohesion: 0.06
Nodes (27): ResolveAtendenteContext middleware, TokenClienteInvalidoException, EnableAtendenteAuthRlsBypass, EnsureAdminApiKey, EnsureAutorizadoEnviarMensagem, EnsureParticipanteChamado, EnsureScopeEscreverCliente, EnsureValidTokenCliente (+19 more)

### Community 3 - "Queue & Job Best Practices"
Cohesion: 0.10
Nodes (21): Queue & Job Best Practices, Bus::batch() Batch Related Jobs, Use Horizon for Complex Queue Scenarios, RateLimited Middleware for Jobs, retry_after Greater Than timeout, retryUntil() Needs tries=0, ShouldBeUnique, ShouldBeUniqueUntilProcessing (+13 more)

### Community 4 - "composer.json"
Cohesion: 0.04
Nodes (46): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+38 more)

### Community 5 - "Database Performance Best Practices"
Cohesion: 0.06
Nodes (30): Database Performance Best Practices, Chunk / chunkById Large Datasets, cursor() Memory-Efficient Iteration, Eager Load Relationships (N+1 prevention), Add Database Indexes, No Queries in Blade Templates, Select Only Needed Columns, withCount() for Counting Relations (+22 more)

### Community 6 - "ClienteAutenticadoBroadcast"
Cohesion: 0.14
Nodes (4): ClienteAutenticadoBroadcast, AutorizarCanalChamadoService, Illuminate\Contracts\Auth\Authenticatable, Illuminate\Support\Facades\Broadcast

### Community 7 - "scripts"
Cohesion: 0.08
Nodes (27): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+19 more)

### Community 8 - "ListarFilaChamadosService.php"
Cohesion: 0.08
Nodes (28): BelongsToSistema trait, RLS policy chamados_sistemas_permitidos_atendente, CHAT-021 histórico consolidado multi-sistema, SistemaContext::definirSistemasPermitidosAtendente, GUC app.current_sistema_id (isolamento cliente), GUC app.sistemas_permitidos_atendente (fila do atendente), Risco: policy OR de chamados vaza se os dois GUCs coexistirem sujos, SistemaScope (+20 more)

### Community 9 - "ProvisionarAtendenteExternoService"
Cohesion: 0.10
Nodes (23): atendente_sistema (tabela de vínculo), AtendenteContext::sistemasPermitidos(), Atendente Externo (CHAT-005B), ProvisionarAtendenteExternoService, Bypass de global scope/RLS só no lookup (mecanismo), EnableAtendenteAuthRlsBypass (middleware), EnsureValidTokenCliente (middleware), Idempotência via firstOrCreate (não catch de unique) (+15 more)

### Community 10 - "Eloquent Best Practices"
Cohesion: 0.25
Nodes (9): Eloquent Best Practices, Define Attribute Casts, Avoid Hardcoded Table Names in Queries, Cast Date Columns Properly, Apply Global Scopes Sparingly, Local Scopes for Reusable Queries, Correct Relationship Types (hasMany/belongsTo), whereBelongsTo() for Relationship Queries (+1 more)

### Community 11 - "User"
Cohesion: 0.16
Nodes (9): User, static, UserFactory, DatabaseSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Foundation\Auth\User (+1 more)

### Community 12 - "GeradorTokenTeste"
Cohesion: 0.07
Nodes (5): ClaimTokenCliente, issDoToken(), GeradorTokenTeste, chavePublicaDoHeader(), motivosDeInvalidez()

### Community 13 - "ValidarTokenClienteService"
Cohesion: 0.12
Nodes (5): ValidarTokenClienteService, UpdateSistemaService, CacheSistema, Firebase\JWT\JWK, OpenSSLAsymmetricKey

### Community 14 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 15 - "What You Must Do When Invoked"
Cohesion: 0.08
Nodes (24): For /graphify add and --watch, For /graphify query, For the commit hook and native CLAUDE.md integration, For --update and --cluster-only, /graphify, Honesty Rules, Interpreter guard for subcommands, Part A - Structural extraction for code files (+16 more)

### Community 16 - "Events & Notifications Best Practices"
Cohesion: 0.16
Nodes (14): Events & Notifications Best Practices, afterCommit() on Notifications, Always Queue Notifications (ShouldQueue), Route Notification Channels to Dedicated Queues, Event Discovery / event:cache, HasLocalePreference on Notifiable Models, On-Demand Notifications, ShouldDispatchAfterCommit (+6 more)

### Community 17 - "ContratoTokenCliente"
Cohesion: 0.15
Nodes (6): .ai/rules/tokens.md, obrigatoria(), opcionais(), ContratoTokenCliente, Encrypt Sensitive Database Fields, Motivos de Invalidez (tabela fechada)

### Community 18 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.22
Nodes (5): StoreChamadoRequest, StoreMensagemRequest, Illuminate\Contracts\Validation\ValidationRule, Illuminate\Foundation\Http\FormRequest, Illuminate\Validation\Rules\Enum

### Community 19 - "laravel-best-practices skill"
Cohesion: 0.17
Nodes (12): Um Service por ação, controller magro, StoreSistemaService (exemplo), addSelect() correlated subqueries for single values, Single-Purpose Action Classes, $attributes->merge() in component templates, @pushOnce for per-component scripts, View Composers for shared view data, Cache::flexible() stale-while-revalidate (+4 more)

### Community 20 - "Chamado"
Cohesion: 0.05
Nodes (25): ChamadoAssumido, MensagemEnviada, Chamado, sistema(), FluxoDefinicao, Mensagem, StoreChamadoService, AvancarFluxoService (+17 more)

### Community 21 - "StoreMensagemTest.php"
Cohesion: 0.18
Nodes (8): Illuminate\Auth\AuthenticationException, Illuminate\Database\QueryException, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Routing\Middleware\ThrottleRequests, Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Event, Illuminate\Support\Facades\Hash, criarAtendenteNoSistema()

### Community 22 - "Routing & Controllers Best Practices"
Cohesion: 0.17
Nodes (11): Routing & Controllers Best Practices, Implicit Route Model Binding, Use Resource Controllers, Scoped Bindings for Nested Resources, Keep Controllers Thin, Type-Hint Form Requests, Validation & Forms Best Practices, after() Method for Custom Validation (+3 more)

### Community 23 - "Pest.php"
Cohesion: 0.20
Nodes (8): Illuminate\Foundation\Testing\TestCase, Illuminate\Testing\TestResponse, autorizarCanalDoChamado(), criarAtendente(), prepararTabelaSistemasParaTeste(), sistemaContext(), tokenAtendente(), TestCase

### Community 24 - "Configuração do Horizon"
Cohesion: 0.18
Nodes (11): horizon:snapshot scheduling for metrics, metrics.trim_snapshots (snapshot count, not duration), Horizon::routeMailNotificationsTo / routeSlackNotificationsTo, waits config (LongWaitDetected threshold), balance: false for fixed worker count, balanceCooldown / balanceMaxShift (anti burst scaling), environments merges into defaults (Horizon config), Named supervisors to enforce queue priority (+3 more)

### Community 25 - "graphify reference: extra exports and benchmark"
Cohesion: 0.22
Nodes (8): graphify reference: extra exports and benchmark, Step 6b - Wiki (only if --wiki flag), Step 7 - Neo4j export (only if --neo4j or --neo4j-push flag), Step 7a - FalkorDB export (only if --falkordb or --falkordb-push flag), Step 7b - SVG export (only if --svg flag), Step 7c - GraphML export (only if --graphml flag), Step 7d - MCP server (only if --mcp flag), Step 8 - Token reduction benchmark (only if total_words > 5000)

### Community 27 - "HorizonServiceProvider"
Cohesion: 0.29
Nodes (4): HorizonServiceProvider, Illuminate\Support\Facades\Gate, Laravel\Horizon\Horizon, Laravel\Horizon\HorizonApplicationServiceProvider

### Community 28 - "Contrato do token assinado — sistema integrado → chat service"
Cohesion: 0.14
Nodes (15): Canal privado de um chamado (broadcasting) — CHAT-006, POST /api/broadcasting/auth Endpoint, private-chamado.{chamado_id} Echo Channel, Regra de Autorização (cliente vs atendente por sistema), Renovação de Token (client-side responsibility), Contrato do token assinado — sistema integrado → chat service, Cache do Cadastro do Sistema (invalidação explícita), Cadastro do Sistema (codigo, jwks_url, status) (+7 more)

### Community 29 - "Regras de Error Handling"
Cohesion: 0.29
Nodes (6): Error Handling Best Practices, Add Context to Exception Classes (context()), Exception Reporting and Rendering (co-located vs centralized), Force JSON Error Rendering for API Routes, ShouldntReport Interface, Throttle High-Volume Exceptions

### Community 30 - "Regras de Scheduling"
Cohesion: 0.29
Nodes (7): Task Scheduling Best Practices, environments() to Restrict Tasks, Schedule Groups for Shared Configuration, onOneServer() on Multi-Server Deployments, runInBackground() for Concurrent Long Tasks, takeUntilTimeout() for Time-Bounded Processing, withoutOverlapping() on Variable-Duration Tasks

### Community 33 - "graphify reference: query, path, explain"
Cohesion: 0.33
Nodes (5): For /graphify explain, For /graphify path, graphify reference: query, path, explain, Step 0 — Constrained query expansion (REQUIRED before traversal), Step 1 — Traversal

### Community 34 - "Conventions & Style Best Practices"
Cohesion: 0.33
Nodes (6): Conventions & Style Best Practices, Laravel Naming Conventions Table, No Inline JS/CSS in Blade, No Unnecessary Comments, Prefer Shorter Readable Syntax, Use Laravel String & Array Helpers (Str/Arr/Number/Uri)

### Community 35 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 36 - "sanctum.php"
Cohesion: 0.40
Nodes (4): Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Foundation\Http\Middleware\ValidateCsrfToken, Laravel\Sanctum\Http\Middleware\AuthenticateSession, Laravel\Sanctum\Sanctum

### Community 37 - "AtendenteContext"
Cohesion: 0.22
Nodes (5): ChamadoController, AssumirChamadoService, ListarFilaChamadosService, AtendenteContext, Illuminate\Http\JsonResponse

### Community 38 - "infer-conventions skill"
Cohesion: 0.50
Nodes (4): Detection Checklist (49 convention dimensions), Consistency First principle (document reality not improve it), infer-conventions skill, record-rule MCP tool

### Community 39 - "Tailwind CSS Development Skill"
Cohesion: 0.50
Nodes (4): Tailwind CSS Development Skill, CSS-First Configuration (@theme directive), Tailwind Dark Mode Variant, Tailwind v4 Replaced Utilities Table

### Community 40 - "plan-task.js"
Cohesion: 0.50
Nodes (3): CONTEXT_SCHEMA, meta, PLAN_SCHEMA

### Community 41 - "graphify reference: add a URL and watch a folder"
Cohesion: 0.50
Nodes (3): For /graphify add, For --watch, graphify reference: add a URL and watch a folder

### Community 42 - "whereIn + subquery over whereHas"
Cohesion: 0.67
Nodes (3): Compound indexes matching orderBy column order, whereIn + subquery over whereHas, toQuery() for bulk operations on collections

### Community 85 - "graphify reference: commit hook and native CLAUDE.md integration"
Cohesion: 0.50
Nodes (3): For git commit hook, For native CLAUDE.md integration, graphify reference: commit hook and native CLAUDE.md integration

### Community 86 - "graphify reference: incremental update and cluster-only"
Cohesion: 0.50
Nodes (3): For --cluster-only, For --update (incremental re-extraction), graphify reference: incremental update and cluster-only

### Community 90 - "SistemaController.php"
Cohesion: 0.24
Nodes (4): SistemaController, StoreSistemaRequest, UpdateSistemaRequest, StoreSistemaService

### Community 91 - "Controller"
Cohesion: 0.22
Nodes (5): MeController, Controller, MensagemController, Illuminate\Broadcasting\BroadcastController, Illuminate\Support\Facades\Route

### Community 92 - "LoginAtendenteRequest"
Cohesion: 0.27
Nodes (3): AuthController, LoginAtendenteRequest, LoginAtendenteService

### Community 93 - "HTTP Client Best Practices"
Cohesion: 0.22
Nodes (9): HTTP Client Best Practices, Fake HTTP Calls in Tests (Http::fake/preventStrayRequests), Handle Errors Explicitly (throw()), Request Pooling for Concurrent Requests, Retry with Backoff for External APIs, Always Set Explicit Timeouts, Exponential Backoff for Job Retries, Cache e Refetch do JWKS (§3.2) (+1 more)

### Community 94 - "Security Best Practices"
Cohesion: 0.22
Nodes (9): Security Best Practices, Audit Dependencies (composer audit), Authorize Every Action (policies/gates), CSRF Protection, Escape Output to Prevent XSS, Mass Assignment Protection ($fillable/$guarded), Rate Limit Auth and API Routes, Validate File Uploads (+1 more)

### Community 96 - ".ai/rules/index.md"
Cohesion: 0.33
Nodes (5): .ai/rules/atendente-externo.md, .ai/rules/chamado-fila.md, .ai/rules/controllers.md, .ai/rules/mensagem.md, .ai/rules/services-mensagem.md

### Community 97 - "Configuration Best Practices"
Cohesion: 0.33
Nodes (6): Configuration Best Practices, App::environment() Checks, Constants and Language Files, Encrypted Env / External Secrets, env() Only in Config Files, Keep Secrets Out of Code

## Ambiguous Edges - Review These
- `$attributes->merge() in component templates` → `View Composers for shared view data`  [AMBIGUOUS]
  .claude/skills/laravel-best-practices/rules/blade-views.md · relation: conceptually_related_to
- `#[CollectedBy] for custom collection classes` → `Higher-Order Messages for simple operations`  [AMBIGUOUS]
  .claude/skills/laravel-best-practices/rules/collections.md · relation: conceptually_related_to
- `Use Context facade for request-scoped data` → `Use defer() for Post-Response Work`  [AMBIGUOUS]
  .claude/skills/laravel-best-practices/rules/architecture.md · relation: conceptually_related_to
- `Cache Tags for group invalidation` → `Failover cache stores in production`  [AMBIGUOUS]
  .claude/skills/laravel-best-practices/rules/caching.md · relation: conceptually_related_to

## Knowledge Gaps
- **185 isolated node(s):** `meta`, `CONTEXT_SCHEMA`, `PLAN_SCHEMA`, `php`, `$schema` (+180 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **26 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `$attributes->merge() in component templates` and `View Composers for shared view data`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **What is the exact relationship between `#[CollectedBy] for custom collection classes` and `Higher-Order Messages for simple operations`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **What is the exact relationship between `Use Context facade for request-scoped data` and `Use defer() for Post-Response Work`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **What is the exact relationship between `Cache Tags for group invalidation` and `Failover cache stores in production`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **Why does `Chamado` connect `Chamado` to `BuscarJwksSegurancaService`, `Atendente`, `SistemaContext`, `AtendenteContext`, `ClienteAutenticadoBroadcast`, `ListarFilaChamadosService.php`, `StoreMensagemTest.php`, `Pest.php`, `Contrato do token assinado — sistema integrado → chat service`?**
  _High betweenness centrality (0.076) - this node is a cross-community bridge._
- **Why does `GeradorTokenTeste` connect `GeradorTokenTeste` to `BuscarJwksSegurancaService`, `ContratoTokenCliente`, `Sistema`, `StoreMensagemTest.php`?**
  _High betweenness centrality (0.071) - this node is a cross-community bridge._
- **Why does `Sistema` connect `Sistema` to `BuscarJwksSegurancaService`, `Atendente`, `ValidarTokenClienteService`, `RepositorioJwks`, `Chamado`, `StoreMensagemTest.php`, `Pest.php`, `SistemaController.php`?**
  _High betweenness centrality (0.070) - this node is a cross-community bridge._