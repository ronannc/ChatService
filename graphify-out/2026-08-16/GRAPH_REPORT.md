# Graph Report - ChatService  (2026-08-16)

## Corpus Check
- 207 files · ~71,700 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 922 nodes · 1511 edges · 89 communities (69 shown, 20 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 28 edges (avg confidence: 0.77)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `2df6eca4`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Http\JsonResponse
- Illuminate\Http\Request
- Fila / leitura multi-sistema de Chamado e Mensagem
- composer.json
- ValidarTokenClienteService
- GeradorTokenTeste
- Pest Testing Skill
- ProvisionarAtendenteExternoService
- Queue & Job Best Practices
- Chamado
- Sistema
- scripts
- Contrato do token assinado — sistema integrado → chat service
- User
- /team command
- Pest.php
- Atendente
- devDependencies
- Events & Notifications Best Practices
- ClienteAutenticadoBroadcast
- Routing & Controllers Best Practices
- Eloquent Best Practices
- What You Must Do When Invoked
- Security Best Practices
- configuring-horizon skill
- HTTP Client Best Practices
- HorizonServiceProvider
- Illuminate\Database\Migrations\Migration
- AppServiceProvider.php
- Error Handling Best Practices
- Task Scheduling Best Practices
- plan-task.js
- Illuminate\Support\Facades\Schema
- graphify reference: extra exports and benchmark
- AutorizarCanalChamadoService
- logging.php
- sanctum.php
- whereIn + subquery over whereHas
- Configuration Best Practices
- Illuminate\Database\Schema\Blueprint
- SistemaScope.php
- console.php
- laravel-boost
- Code to Interfaces at system boundaries
- Use Context facade for request-scoped data
- Cache::memo() avoid redundant hits per request
- Cache Tags for group invalidation
- cursor() vs lazy() choice
- entrypoint.sh
- init.sh
- Enviar métricas para 4TechLead Workflow
- Use Concurrency::run() for Parallel Execution
- SistemaContext
- graphify reference: query, path, explain
- graphify reference: add a URL and watch a folder
- graphify reference: commit hook and native CLAUDE.md integration
- graphify reference: incremental update and cluster-only
- graphify reference: GitHub clone and cross-repo merge
- graphify reference: transcribe video and audio
- CLAUDE.md
- extraction-spec.md

## God Nodes (most connected - your core abstractions)
1. `GeradorTokenTeste` - 49 edges
2. `Sistema` - 47 edges
3. `Atendente` - 38 edges
4. `Chamado` - 36 edges
5. `ValidarTokenClienteService` - 31 edges
6. `SistemaContext` - 30 edges
7. `RepositorioJwks` - 23 edges
8. `BuscarJwksSegurancaService` - 18 edges
9. `ContratoTokenCliente` - 18 edges
10. `Mensagem` - 17 edges

## Surprising Connections (you probably didn't know these)
- `Endpoint JWKS (RFC 7517)` --semantically_similar_to--> `Always Set Explicit Timeouts`  [INFERRED] [semantically similar]
  docs/contratos/token-cliente.md → .claude/skills/laravel-best-practices/rules/http-client.md
- `Cache e Refetch do JWKS (§3.2)` --semantically_similar_to--> `Retry with Backoff for External APIs`  [INFERRED] [semantically similar]
  docs/contratos/token-cliente.md → .claude/skills/laravel-best-practices/rules/http-client.md
- `Single-Purpose Action Classes` --semantically_similar_to--> `Um Service por ação, controller magro`  [INFERRED] [semantically similar]
  .claude/skills/laravel-best-practices/rules/architecture.md → .ai/rules/controllers.md
- `Use Atomic Locks for Race Conditions` --semantically_similar_to--> `Idempotência via firstOrCreate (não catch de unique)`  [INFERRED] [semantically similar]
  .claude/skills/laravel-best-practices/rules/architecture.md → .ai/rules/atendente-externo.md
- `tokenAtendente()` --references--> `Atendente`  [EXTRACTED]
  tests/Pest.php → app/Models/Atendente.php

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **RLS bypass mechanism for cross-system atendente lookup** — ai_rules_atendente_externo_atendenteexternoprovisionamentoservice, ai_rules_atendente_externo_sistemacontext_guc_bypass, ai_rules_atendente_externo_loginatendenteservice_buscarporemailbypassandorls, ai_rules_atendente_externo_risco_savepoint_set_local [EXTRACTED 1.00]
- **ChatService agent team roles collaborating on a diff** — claude_agents_dev_laravel_dev_laravel, claude_agents_qa_pest_qa_pest, claude_agents_po_validator_po_validator, claude_agents_perf_specialist_perf_specialist, claude_agents_security_reviewer_security_reviewer [EXTRACTED 1.00]
- **Local Dev Environment via Docker Compose** — claude_md_docker_compose_local_env, readme_docker_local_environment, docker_compose, readme_configuration_table [EXTRACTED 1.00]
- **Dual-auth mensagem routes sharing one URI across cliente/atendente middlewares** — ai_rules_services_mensagem_identificarclientemensagem, ai_rules_services_mensagem_identificaratendentemensagem, ai_rules_services_mensagem_mensagens_isolamento_sistema_policy, ai_rules_services_mensagem_dual_auth_middleware_pattern [EXTRACTED 1.00]
- **Token Validation Pipeline (contract, code, tests)** — docs_contratos_token_cliente, app_enums_claimtokencliente, app_support_contratotokencliente, tests_support_geradortokenteste [EXTRACTED 1.00]
- **Broadcast Channel Authorization Flow** — docs_contratos_canal_chamado_broadcast, app_services_broadcasting_autorizarcanalchamadoservice, docs_contratos_token_cliente, docker_compose_reverb_service [INFERRED 0.85]

## Communities (89 total, 20 thin omitted)

### Community 0 - "Illuminate\Http\JsonResponse"
Cohesion: 0.06
Nodes (21): SistemaController, AuthController, MeController, ChamadoController, Controller, StoreSistemaRequest, UpdateSistemaRequest, LoginAtendenteRequest (+13 more)

### Community 1 - "Illuminate\Http\Request"
Cohesion: 0.10
Nodes (20): EnableAtendenteAuthRlsBypass, EnsureAdminApiKey, EnsureAutorizadoEnviarMensagem, EnsureParticipanteChamado, EnsureScopeEscreverCliente, EnsureValidTokenCliente, IdentificarAtendenteMensagem, IdentificarClienteMensagem (+12 more)

### Community 2 - "Fila / leitura multi-sistema de Chamado e Mensagem"
Cohesion: 0.40
Nodes (4): Fila / leitura multi-sistema de Chamado e Mensagem, Risco latente: policy OR de `chamados` vaza se os dois GUCs coexistirem sujos na mesma conexão, SistemaScope aplica `whereRaw(1=0)` quando `SistemaContext->get()` é null, Índice parcial para filtro por status transitório + ordenação

### Community 3 - "composer.json"
Cohesion: 0.04
Nodes (46): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+38 more)

### Community 4 - "ValidarTokenClienteService"
Cohesion: 0.10
Nodes (7): obrigatoria(), opcionais(), ValidarTokenClienteService, ContratoTokenCliente, Motivos de Invalidez (tabela fechada), Firebase\JWT\JWK, OpenSSLAsymmetricKey

### Community 5 - "GeradorTokenTeste"
Cohesion: 0.07
Nodes (5): ClaimTokenCliente, issDoToken(), GeradorTokenTeste, chavePublicaDoHeader(), motivosDeInvalidez()

### Community 6 - "Pest Testing Skill"
Cohesion: 0.05
Nodes (37): Laravel Boost Guidelines (embedded), Database Performance Best Practices, Chunk / chunkById Large Datasets, cursor() Memory-Efficient Iteration, Eager Load Relationships (N+1 prevention), Add Database Indexes, No Queries in Blade Templates, Select Only Needed Columns (+29 more)

### Community 7 - "ProvisionarAtendenteExternoService"
Cohesion: 0.08
Nodes (29): atendente_sistema (tabela de vínculo), AtendenteContext::sistemasPermitidos(), Atendente Externo (CHAT-005B), ProvisionarAtendenteExternoService, Bypass de global scope/RLS só no lookup (mecanismo), EnableAtendenteAuthRlsBypass (middleware), EnsureValidTokenCliente (middleware), Idempotência via firstOrCreate (não catch de unique) (+21 more)

### Community 8 - "Queue & Job Best Practices"
Cohesion: 0.08
Nodes (28): CLAUDE.md Project Guidance, CHAT-003 Postgres Non-Superuser Role for RLS, Docker Compose as Real Local Environment, Queue & Job Best Practices, Bus::batch() Batch Related Jobs, Use Horizon for Complex Queue Scenarios, RateLimited Middleware for Jobs, retry_after Greater Than timeout (+20 more)

### Community 9 - "Chamado"
Cohesion: 0.09
Nodes (19): MensagemEnviada, MensagemController, Chamado, sistema(), Mensagem, ListarMensagensService, StoreMensagemService, MensagemFactory (+11 more)

### Community 10 - "Sistema"
Cohesion: 0.05
Nodes (30): TokenClienteInvalidoException, AtendenteSistema, Sistema, BuscarJwksSegurancaService, RepositorioJwks, GuardaHostSeguro, AtendenteFactory, static (+22 more)

### Community 11 - "scripts"
Cohesion: 0.08
Nodes (27): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+19 more)

### Community 12 - "Contrato do token assinado — sistema integrado → chat service"
Cohesion: 0.15
Nodes (15): Canal privado de um chamado (broadcasting) — CHAT-006, POST /api/broadcasting/auth Endpoint, private-chamado.{chamado_id} Echo Channel, Regra de Autorização (cliente vs atendente por sistema), Renovação de Token (client-side responsibility), Contrato do token assinado — sistema integrado → chat service, Cache do Cadastro do Sistema (invalidação explícita), Cadastro do Sistema (codigo, jwks_url, status) (+7 more)

### Community 13 - "User"
Cohesion: 0.11
Nodes (11): User, static, UserFactory, DatabaseSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Foundation\Auth\User (+3 more)

### Community 14 - "/team command"
Cohesion: 0.11
Nodes (23): Um Service por ação, controller magro, StoreSistemaService (exemplo), dev-laravel agent, perf-specialist agent, po-validator agent, qa-pest agent, security-reviewer agent, Posse de arquivo dev x qa (fronteira reforçada por hook) (+15 more)

### Community 15 - "Pest.php"
Cohesion: 0.20
Nodes (8): Illuminate\Foundation\Testing\TestCase, Illuminate\Testing\TestResponse, autorizarCanalDoChamado(), criarAtendente(), prepararTabelaSistemasParaTeste(), sistemaContext(), tokenAtendente(), TestCase

### Community 16 - "Atendente"
Cohesion: 0.16
Nodes (6): Atendente, AtendenteContext, Illuminate\Auth\Authenticatable, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Support\Collection, Laravel\Sanctum\HasApiTokens

### Community 17 - "devDependencies"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 18 - "Events & Notifications Best Practices"
Cohesion: 0.16
Nodes (14): Events & Notifications Best Practices, afterCommit() on Notifications, Always Queue Notifications (ShouldQueue), Route Notification Channels to Dedicated Queues, Event Discovery / event:cache, HasLocalePreference on Notifiable Models, On-Demand Notifications, ShouldDispatchAfterCommit (+6 more)

### Community 20 - "Routing & Controllers Best Practices"
Cohesion: 0.17
Nodes (11): Routing & Controllers Best Practices, Implicit Route Model Binding, Use Resource Controllers, Scoped Bindings for Nested Resources, Keep Controllers Thin, Type-Hint Form Requests, Validation & Forms Best Practices, after() Method for Custom Validation (+3 more)

### Community 21 - "Eloquent Best Practices"
Cohesion: 0.25
Nodes (9): Eloquent Best Practices, Define Attribute Casts, Avoid Hardcoded Table Names in Queries, Cast Date Columns Properly, Apply Global Scopes Sparingly, Local Scopes for Reusable Queries, Correct Relationship Types (hasMany/belongsTo), whereBelongsTo() for Relationship Queries (+1 more)

### Community 22 - "What You Must Do When Invoked"
Cohesion: 0.08
Nodes (24): For /graphify add and --watch, For /graphify query, For the commit hook and native CLAUDE.md integration, For --update and --cluster-only, /graphify, Honesty Rules, Interpreter guard for subcommands, Part A - Structural extraction for code files (+16 more)

### Community 23 - "Security Best Practices"
Cohesion: 0.18
Nodes (10): Security Best Practices, Audit Dependencies (composer audit), Authorize Every Action (policies/gates), CSRF Protection, Encrypt Sensitive Database Fields, Escape Output to Prevent XSS, Mass Assignment Protection ($fillable/$guarded), Rate Limit Auth and API Routes (+2 more)

### Community 24 - "configuring-horizon skill"
Cohesion: 0.18
Nodes (11): horizon:snapshot scheduling for metrics, metrics.trim_snapshots (snapshot count, not duration), Horizon::routeMailNotificationsTo / routeSlackNotificationsTo, waits config (LongWaitDetected threshold), balance: false for fixed worker count, balanceCooldown / balanceMaxShift (anti burst scaling), environments merges into defaults (Horizon config), Named supervisors to enforce queue priority (+3 more)

### Community 25 - "HTTP Client Best Practices"
Cohesion: 0.22
Nodes (9): HTTP Client Best Practices, Fake HTTP Calls in Tests (Http::fake/preventStrayRequests), Handle Errors Explicitly (throw()), Request Pooling for Concurrent Requests, Retry with Backoff for External APIs, Always Set Explicit Timeouts, Exponential Backoff for Job Retries, Cache e Refetch do JWKS (§3.2) (+1 more)

### Community 26 - "HorizonServiceProvider"
Cohesion: 0.29
Nodes (4): HorizonServiceProvider, Illuminate\Support\Facades\Gate, Laravel\Horizon\Horizon, Laravel\Horizon\HorizonApplicationServiceProvider

### Community 28 - "AppServiceProvider.php"
Cohesion: 0.40
Nodes (3): AppServiceProvider, Illuminate\Support\Facades\Auth, Illuminate\Support\ServiceProvider

### Community 29 - "Error Handling Best Practices"
Cohesion: 0.29
Nodes (6): Error Handling Best Practices, Add Context to Exception Classes (context()), Exception Reporting and Rendering (co-located vs centralized), Force JSON Error Rendering for API Routes, ShouldntReport Interface, Throttle High-Volume Exceptions

### Community 30 - "Task Scheduling Best Practices"
Cohesion: 0.29
Nodes (7): Task Scheduling Best Practices, environments() to Restrict Tasks, Schedule Groups for Shared Configuration, onOneServer() on Multi-Server Deployments, runInBackground() for Concurrent Long Tasks, takeUntilTimeout() for Time-Bounded Processing, withoutOverlapping() on Variable-Duration Tasks

### Community 31 - "plan-task.js"
Cohesion: 0.50
Nodes (3): CONTEXT_SCHEMA, meta, PLAN_SCHEMA

### Community 34 - "graphify reference: extra exports and benchmark"
Cohesion: 0.22
Nodes (8): graphify reference: extra exports and benchmark, Step 6b - Wiki (only if --wiki flag), Step 7 - Neo4j export (only if --neo4j or --neo4j-push flag), Step 7a - FalkorDB export (only if --falkordb or --falkordb-push flag), Step 7b - SVG export (only if --svg flag), Step 7c - GraphML export (only if --graphml flag), Step 7d - MCP server (only if --mcp flag), Step 8 - Token reduction benchmark (only if total_words > 5000)

### Community 36 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 37 - "sanctum.php"
Cohesion: 0.40
Nodes (4): Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Foundation\Http\Middleware\ValidateCsrfToken, Laravel\Sanctum\Http\Middleware\AuthenticateSession, Laravel\Sanctum\Sanctum

### Community 38 - "whereIn + subquery over whereHas"
Cohesion: 0.67
Nodes (3): Compound indexes matching orderBy column order, whereIn + subquery over whereHas, toQuery() for bulk operations on collections

### Community 39 - "Configuration Best Practices"
Cohesion: 0.33
Nodes (6): Configuration Best Practices, App::environment() Checks, Constants and Language Files, Encrypted Env / External Secrets, env() Only in Config Files, Keep Secrets Out of Code

### Community 47 - "SistemaScope.php"
Cohesion: 0.60
Nodes (3): SistemaScope, Illuminate\Database\Eloquent\Builder, Illuminate\Database\Eloquent\Scope

### Community 81 - "SistemaContext"
Cohesion: 0.18
Nodes (3): ListarFilaChamadosService, SistemaContext, Illuminate\Pagination\LengthAwarePaginator

### Community 83 - "graphify reference: query, path, explain"
Cohesion: 0.33
Nodes (5): For /graphify explain, For /graphify path, graphify reference: query, path, explain, Step 0 — Constrained query expansion (REQUIRED before traversal), Step 1 — Traversal

### Community 85 - "graphify reference: add a URL and watch a folder"
Cohesion: 0.50
Nodes (3): For /graphify add, For --watch, graphify reference: add a URL and watch a folder

### Community 86 - "graphify reference: commit hook and native CLAUDE.md integration"
Cohesion: 0.50
Nodes (3): For git commit hook, For native CLAUDE.md integration, graphify reference: commit hook and native CLAUDE.md integration

### Community 87 - "graphify reference: incremental update and cluster-only"
Cohesion: 0.50
Nodes (3): For --cluster-only, For --update (incremental re-extraction), graphify reference: incremental update and cluster-only

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
- **171 isolated node(s):** `meta`, `CONTEXT_SCHEMA`, `PLAN_SCHEMA`, `php`, `$schema` (+166 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **20 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

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
- **Why does `GeradorTokenTeste` connect `GeradorTokenTeste` to `Sistema`, `ValidarTokenClienteService`?**
  _High betweenness centrality (0.078) - this node is a cross-community bridge._
- **Why does `Sistema` connect `Sistema` to `Illuminate\Http\JsonResponse`, `Chamado`, `ValidarTokenClienteService`, `Pest.php`?**
  _High betweenness centrality (0.071) - this node is a cross-community bridge._
- **Why does `Pest Testing Skill` connect `Pest Testing Skill` to `ValidarTokenClienteService`?**
  _High betweenness centrality (0.058) - this node is a cross-community bridge._