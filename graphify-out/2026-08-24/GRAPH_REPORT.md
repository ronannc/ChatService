# Graph Report - ChatService  (2026-08-24)

## Corpus Check
- 210 files · ~71,700 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 878 nodes · 1478 edges · 85 communities (60 shown, 25 thin omitted)
- Extraction: 97% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 34 edges (avg confidence: 0.78)
- Token cost: 120,064 input · 0 output

## Community Hubs (Navigation)
- Modelos e Enums de Domínio
- Eventos de Mensagem/Chamado
- Middlewares de Auth/RLS
- Regras HTTP Client/Queue
- Configuração Composer
- Regras Performance de DB
- Service Providers
- Scripts Composer
- Isolamento por Sistema (RLS)
- Provisionamento Atendente Externo
- Regras Eloquent/Config
- Model User e Config Base
- Testes de Token JWT
- Validação de Token Cliente
- Dependências Frontend (Vite)
- Claims de Token Cliente
- Regras Events/Notifications
- Contexto do Atendente Autenticado
- Form Requests de Chamado/Mensagem
- Regras Diversas Laravel Best Practices
- Contrato de Token Cliente
- Controllers de Chamado/Mensagem
- Regras de Routing/Validation
- Controller Admin de Sistema
- Configuração do Horizon
- Login do Atendente
- Migrations de RLS
- Services de Sistema/Cache
- Enum ClaimTokenCliente
- Regras de Error Handling
- Regras de Scheduling
- Migrations Base Laravel
- Migrations de Índices
- Índice de Regras .ai/rules
- Regras de Estilo de Código
- Configuração de Logging
- Middlewares Sanctum/CSRF
- Índice de Fila de Chamados
- Skill infer-conventions
- Skill Tailwind CSS
- Workflow plan-task
- Rotas Web/API
- Regras de Queries Avançadas
- Comandos Artisan Console
- MCP Laravel Boost
- Contexto/Scope de Sistema
- Agent Perf Specialist
- Trigger Graphify no CLAUDE.md
- Regras de Arquitetura DI
- Regras de Facade/Defer
- Regras de Cache Memo
- Regras de Cache Tags
- Regras de Collections Lazy
- Entrypoint Docker PHP
- Init Docker Postgres
- Workflow CI Tech Lead
- Agent QA Pest
- Regra de Concorrência

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
- `Use Atomic Locks for Race Conditions` --semantically_similar_to--> `Idempotência via firstOrCreate (não catch de unique)`  [INFERRED] [semantically similar]
  .claude/skills/laravel-best-practices/rules/architecture.md → .ai/rules/atendente-externo.md
- `Single-Purpose Action Classes` --semantically_similar_to--> `Um Service por ação, controller magro`  [INFERRED] [semantically similar]
  .claude/skills/laravel-best-practices/rules/architecture.md → .ai/rules/controllers.md
- `Endpoint JWKS (RFC 7517)` --semantically_similar_to--> `Always Set Explicit Timeouts`  [INFERRED] [semantically similar]
  docs/contratos/token-cliente.md → .claude/skills/laravel-best-practices/rules/http-client.md
- `Cache e Refetch do JWKS (§3.2)` --semantically_similar_to--> `Retry with Backoff for External APIs`  [INFERRED] [semantically similar]
  docs/contratos/token-cliente.md → .claude/skills/laravel-best-practices/rules/http-client.md
- `Risco: policy OR de chamados vaza se os dois GUCs coexistirem sujos` --semantically_similar_to--> `Isolamento por sistema_id (global scope + RLS)`  [INFERRED] [semantically similar]
  .ai/rules/chamado-fila.md → .claude/agents/dev-laravel.md

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **RLS bypass mechanism for cross-system atendente lookup** — ai_rules_atendente_externo_atendenteexternoprovisionamentoservice, ai_rules_atendente_externo_sistemacontext_guc_bypass, ai_rules_atendente_externo_loginatendenteservice_buscarporemailbypassandorls, ai_rules_atendente_externo_risco_savepoint_set_local [EXTRACTED 1.00]
- **Dual-auth mensagem routes sharing one URI across cliente/atendente middlewares** — ai_rules_services_mensagem_identificarclientemensagem, ai_rules_services_mensagem_identificaratendentemensagem, ai_rules_services_mensagem_mensagens_isolamento_sistema_policy, ai_rules_services_mensagem_dual_auth_middleware_pattern [EXTRACTED 1.00]
- **Broadcast Channel Authorization Flow** — docs_contratos_canal_chamado_broadcast, app_services_broadcasting_autorizarcanalchamadoservice, docs_contratos_token_cliente, docker_compose_reverb_service [INFERRED 0.85]
- **Token Validation Pipeline (contract, code, tests)** — docs_contratos_token_cliente, app_enums_claimtokencliente, app_support_contratotokencliente, tests_support_geradortokenteste [EXTRACTED 1.00]
- **ChatService agent team review flow** — claude_agents_dev_laravel, claude_agents_qa_pest, claude_agents_po_validator, claude_agents_perf_specialist, claude_agents_security_reviewer [EXTRACTED 1.00]
- **Chamados dual-GUC sistema_id isolation mechanism** — ai_rules_chamado_fila_guc_current_sistema_id, ai_rules_chamado_fila_guc_sistemas_permitidos_atendente, ai_rules_chamado_fila_chamados_sistemas_permitidos_atendente_policy, app_services_chamado_listarfilachamadosservice [INFERRED 0.85]
- **Rule files scoped to app code paths via index globs** — ai_rules_index, ai_rules_atendente_externo, ai_rules_controllers, ai_rules_mensagem, ai_rules_services_mensagem, ai_rules_chamado_fila, ai_rules_tokens [EXTRACTED 1.00]

## Communities (85 total, 25 thin omitted)

### Community 0 - "Modelos e Enums de Domínio"
Cohesion: 0.05
Nodes (30): TokenClienteInvalidoException, AtendenteSistema, Sistema, BuscarJwksSegurancaService, RepositorioJwks, GuardaHostSeguro, AtendenteFactory, static (+22 more)

### Community 1 - "Eventos de Mensagem/Chamado"
Cohesion: 0.05
Nodes (36): BelongsToSistema trait, MensagemEnviada, Atendente, Chamado, sistema(), Mensagem, SistemaScope, ListarMensagensService (+28 more)

### Community 2 - "Middlewares de Auth/RLS"
Cohesion: 0.07
Nodes (22): ResolveAtendenteContext middleware, EnableAtendenteAuthRlsBypass, EnsureAdminApiKey, EnsureAutorizadoEnviarMensagem, EnsureParticipanteChamado, EnsureScopeEscreverCliente, EnsureValidTokenCliente, IdentificarAtendenteMensagem (+14 more)

### Community 3 - "Regras HTTP Client/Queue"
Cohesion: 0.05
Nodes (45): HTTP Client Best Practices, Fake HTTP Calls in Tests (Http::fake/preventStrayRequests), Handle Errors Explicitly (throw()), Request Pooling for Concurrent Requests, Retry with Backoff for External APIs, Always Set Explicit Timeouts, Queue & Job Best Practices, Bus::batch() Batch Related Jobs (+37 more)

### Community 4 - "Configuração Composer"
Cohesion: 0.04
Nodes (46): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+38 more)

### Community 5 - "Regras Performance de DB"
Cohesion: 0.06
Nodes (30): Database Performance Best Practices, Chunk / chunkById Large Datasets, cursor() Memory-Efficient Iteration, Eager Load Relationships (N+1 prevention), Add Database Indexes, No Queries in Blade Templates, Select Only Needed Columns, withCount() for Counting Relations (+22 more)

### Community 6 - "Service Providers"
Cohesion: 0.08
Nodes (11): AppServiceProvider, HorizonServiceProvider, ClienteAutenticadoBroadcast, AutorizarCanalChamadoService, Illuminate\Contracts\Auth\Authenticatable, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\Broadcast, Illuminate\Support\Facades\Gate (+3 more)

### Community 7 - "Scripts Composer"
Cohesion: 0.08
Nodes (27): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+19 more)

### Community 8 - "Isolamento por Sistema (RLS)"
Cohesion: 0.11
Nodes (23): RLS policy chamados_sistemas_permitidos_atendente, CHAT-021 histórico consolidado multi-sistema, SistemaContext::definirSistemasPermitidosAtendente, GUC app.current_sistema_id (isolamento cliente), GUC app.sistemas_permitidos_atendente (fila do atendente), Risco: policy OR de chamados vaza se os dois GUCs coexistirem sujos, Auth JWT do cliente final (RS256/JWKS), Auth Sanctum do atendente (+15 more)

### Community 9 - "Provisionamento Atendente Externo"
Cohesion: 0.10
Nodes (23): atendente_sistema (tabela de vínculo), AtendenteContext::sistemasPermitidos(), Atendente Externo (CHAT-005B), ProvisionarAtendenteExternoService, Bypass de global scope/RLS só no lookup (mecanismo), EnableAtendenteAuthRlsBypass (middleware), EnsureValidTokenCliente (middleware), Idempotência via firstOrCreate (não catch de unique) (+15 more)

### Community 10 - "Regras Eloquent/Config"
Cohesion: 0.09
Nodes (24): Configuration Best Practices, App::environment() Checks, Constants and Language Files, Encrypted Env / External Secrets, env() Only in Config Files, Eloquent Best Practices, Define Attribute Casts, Avoid Hardcoded Table Names in Queries (+16 more)

### Community 11 - "Model User e Config Base"
Cohesion: 0.11
Nodes (11): User, static, UserFactory, DatabaseSeeder, Illuminate\Database\Console\Seeds\WithoutModelEvents, Illuminate\Database\Eloquent\Attributes\Fillable, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Foundation\Auth\User (+3 more)

### Community 14 - "Dependências Frontend (Vite)"
Cohesion: 0.11
Nodes (17): concurrently, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite (+9 more)

### Community 16 - "Regras Events/Notifications"
Cohesion: 0.16
Nodes (14): Events & Notifications Best Practices, afterCommit() on Notifications, Always Queue Notifications (ShouldQueue), Route Notification Channels to Dedicated Queues, Event Discovery / event:cache, HasLocalePreference on Notifiable Models, On-Demand Notifications, ShouldDispatchAfterCommit (+6 more)

### Community 17 - "Contexto do Atendente Autenticado"
Cohesion: 0.19
Nodes (4): MeController, Controller, AtendenteContext, Illuminate\Support\Collection

### Community 18 - "Form Requests de Chamado/Mensagem"
Cohesion: 0.22
Nodes (5): StoreChamadoRequest, StoreMensagemRequest, Illuminate\Contracts\Validation\ValidationRule, Illuminate\Foundation\Http\FormRequest, Illuminate\Validation\Rules\Enum

### Community 19 - "Regras Diversas Laravel Best Practices"
Cohesion: 0.17
Nodes (12): Um Service por ação, controller magro, StoreSistemaService (exemplo), addSelect() correlated subqueries for single values, Single-Purpose Action Classes, $attributes->merge() in component templates, @pushOnce for per-component scripts, View Composers for shared view data, Cache::flexible() stale-while-revalidate (+4 more)

### Community 20 - "Contrato de Token Cliente"
Cohesion: 0.21
Nodes (6): .ai/rules/tokens.md, ContratoTokenCliente, Encrypt Sensitive Database Fields, Motivos de Invalidez (tabela fechada), chavePublicaDoHeader(), motivosDeInvalidez()

### Community 21 - "Controllers de Chamado/Mensagem"
Cohesion: 0.24
Nodes (5): ChamadoController, MensagemController, ListarFilaChamadosService, StoreChamadoService, Illuminate\Http\JsonResponse

### Community 22 - "Regras de Routing/Validation"
Cohesion: 0.17
Nodes (11): Routing & Controllers Best Practices, Implicit Route Model Binding, Use Resource Controllers, Scoped Bindings for Nested Resources, Keep Controllers Thin, Type-Hint Form Requests, Validation & Forms Best Practices, after() Method for Custom Validation (+3 more)

### Community 23 - "Controller Admin de Sistema"
Cohesion: 0.24
Nodes (4): SistemaController, StoreSistemaRequest, UpdateSistemaRequest, StoreSistemaService

### Community 24 - "Configuração do Horizon"
Cohesion: 0.18
Nodes (11): horizon:snapshot scheduling for metrics, metrics.trim_snapshots (snapshot count, not duration), Horizon::routeMailNotificationsTo / routeSlackNotificationsTo, waits config (LongWaitDetected threshold), balance: false for fixed worker count, balanceCooldown / balanceMaxShift (anti burst scaling), environments merges into defaults (Horizon config), Named supervisors to enforce queue priority (+3 more)

### Community 25 - "Login do Atendente"
Cohesion: 0.27
Nodes (3): AuthController, LoginAtendenteRequest, LoginAtendenteService

### Community 29 - "Regras de Error Handling"
Cohesion: 0.29
Nodes (6): Error Handling Best Practices, Add Context to Exception Classes (context()), Exception Reporting and Rendering (co-located vs centralized), Force JSON Error Rendering for API Routes, ShouldntReport Interface, Throttle High-Volume Exceptions

### Community 30 - "Regras de Scheduling"
Cohesion: 0.29
Nodes (7): Task Scheduling Best Practices, environments() to Restrict Tasks, Schedule Groups for Shared Configuration, onOneServer() on Multi-Server Deployments, runInBackground() for Concurrent Long Tasks, takeUntilTimeout() for Time-Bounded Processing, withoutOverlapping() on Variable-Duration Tasks

### Community 33 - "Índice de Regras .ai/rules"
Cohesion: 0.33
Nodes (5): .ai/rules/atendente-externo.md, .ai/rules/chamado-fila.md, .ai/rules/controllers.md, .ai/rules/mensagem.md, .ai/rules/services-mensagem.md

### Community 34 - "Regras de Estilo de Código"
Cohesion: 0.33
Nodes (6): Conventions & Style Best Practices, Laravel Naming Conventions Table, No Inline JS/CSS in Blade, No Unnecessary Comments, Prefer Shorter Readable Syntax, Use Laravel String & Array Helpers (Str/Arr/Number/Uri)

### Community 35 - "Configuração de Logging"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 36 - "Middlewares Sanctum/CSRF"
Cohesion: 0.40
Nodes (4): Illuminate\Cookie\Middleware\EncryptCookies, Illuminate\Foundation\Http\Middleware\ValidateCsrfToken, Laravel\Sanctum\Http\Middleware\AuthenticateSession, Laravel\Sanctum\Sanctum

### Community 38 - "Skill infer-conventions"
Cohesion: 0.50
Nodes (4): Detection Checklist (49 convention dimensions), Consistency First principle (document reality not improve it), infer-conventions skill, record-rule MCP tool

### Community 39 - "Skill Tailwind CSS"
Cohesion: 0.50
Nodes (4): Tailwind CSS Development Skill, CSS-First Configuration (@theme directive), Tailwind Dark Mode Variant, Tailwind v4 Replaced Utilities Table

### Community 40 - "Workflow plan-task"
Cohesion: 0.50
Nodes (3): CONTEXT_SCHEMA, meta, PLAN_SCHEMA

### Community 42 - "Regras de Queries Avançadas"
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
- **144 isolated node(s):** `meta`, `CONTEXT_SCHEMA`, `PLAN_SCHEMA`, `php`, `$schema` (+139 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **25 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

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
- **Why does `GeradorTokenTeste` connect `Testes de Token JWT` to `Modelos e Enums de Domínio`, `Contrato de Token Cliente`, `Enum ClaimTokenCliente`, `Claims de Token Cliente`?**
  _High betweenness centrality (0.082) - this node is a cross-community bridge._
- **Why does `Sistema` connect `Modelos e Enums de Domínio` to `Eventos de Mensagem/Chamado`, `Services de Sistema/Cache`, `Validação de Token Cliente`, `Controller Admin de Sistema`?**
  _High betweenness centrality (0.079) - this node is a cross-community bridge._
- **Why does `Chamado` connect `Eventos de Mensagem/Chamado` to `Modelos e Enums de Domínio`, `Middlewares de Auth/RLS`, `Regras HTTP Client/Queue`, `Service Providers`, `Controllers de Chamado/Mensagem`?**
  _High betweenness centrality (0.068) - this node is a cross-community bridge._