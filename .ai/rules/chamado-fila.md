---
paths:
  - 'app/Services/Chamado/**,app/Services/Mensagem/**'
---

# Fila / leitura multi-sistema de Chamado e Mensagem

## SistemaScope aplica `whereRaw(1=0)` quando `SistemaContext->get()` é null
No grupo de rotas Sanctum de atendente interno, `SistemaContext::set()` nunca é chamado — só `ResolveAtendenteContext`, que apenas desativa o bypass de resolução de atendente (`app/Http/Middleware/EnableAtendenteAuthRlsBypass.php`). Isso significa que `SistemaContext->get()` fica `null` durante toda a request, e `SistemaScope::apply()` (`app/Models/Scopes/SistemaScope.php`) injeta `whereRaw('1 = 0')` em qualquer query de model que use `BelongsToSistema` (Chamado, Mensagem) antes mesmo da RLS do Postgres entrar em jogo.

Qualquer leitura multi-sistema de `Chamado`/`Mensagem` feita a partir do contexto de atendente Sanctum (não do contexto de cliente final via `cliente.token`, que chama `SistemaContext::set()` a partir do `iss` do JWT) precisa bypassar esse global scope explicitamente com `Chamado::withoutGlobalScope(\App\Models\Scopes\SistemaScope::class)`, e então filtrar manualmente com `whereIn('sistema_id', $codigos)` — client-side, defesa em profundidade, não substitui a RLS. Implementado em `App\Services\Chamado\ListarFilaChamadosService` (CHAT-010) e confirmado empiricamente via tinker contra o Postgres real (chamado de sistema não-permitido não aparece no resultado).

Reaproveitar `SistemaContext::definirSistemasPermitidosAtendente($codigos)` / `limparSistemasPermitidosAtendente()` (dentro de um `finally`) para propagar a lista ao GUC lido pela policy RLS `chamados_sistemas_permitidos_atendente` (criada em CHAT-006, migration `2026_08_12_100001_add_rls_policy_sistemas_permitidos_atendente_em_chamados.php`) — não criar policy nova. Relevante para CHAT-021 (histórico consolidado multi-sistema), que vai bater no mesmo problema.

## Índice parcial para filtro por status transitório + ordenação
`chamados` não arquiva/expira linhas com status final (`resolvido`/`finalizado`), então o volume por `sistema_id` cresce indefinidamente enquanto a fração em `aguardando_fila` é pequena e transitória. Sem índice dedicado, `WHERE status = 'aguardando_fila' AND sistema_id IN (...) ORDER BY created_at, id` cai num `Index Scan` pelo índice composto `(sistema_id, cliente_ref)` com `status` como `Filter` pós-index e um `Sort` explícito — degrada com o crescimento da tabela, num endpoint de polling de alta frequência (`/fila`).

Migration `2026_08_16_190000_add_index_fila_por_sistema_a_chamados_table.php` criou `chamados_fila_por_sistema_index` — índice parcial `(sistema_id, created_at) WHERE status = 'aguardando_fila'` (via `DB::statement` bruto; Laravel não expõe partial index fluente no Postgres). Confirmado via `EXPLAIN (ANALYZE, BUFFERS)` com 2500 linhas populadas (500 por status) que o planner passou a usar esse índice com `Incremental Sort` em vez do índice composto antigo + `Sort` completo. Qualquer novo filtro por status transitório sobre tabela que não arquiva linhas finais deve considerar o mesmo padrão de índice parcial em vez de índice composto cobrindo todos os valores de status.

## Risco latente: policy OR de `chamados` vaza se os dois GUCs coexistirem sujos na mesma conexão
Achado do security review de CHAT-010, confirmado empiricamente contra o Postgres real: se `app.current_sistema_id` (GUC de isolamento do fluxo cliente) e `app.sistemas_permitidos_atendente` (GUC de fila do atendente) ficarem setados simultaneamente e "sujos" na mesma sessão de conexão, a policy OR (`chamados_isolamento_sistema` OR `chamados_sistemas_permitidos_atendente`) vaza a união dos dois conjuntos de sistemas — cada policy sozinha filtra corretamente, mas a combinação por OR não tem como saber que só um dos dois GUCs deveria estar "ativo" para aquela request.

Hoje isso **não é explorável**: cada request PHP-FPM abre conexão nova ao Postgres (sem PDO persistente, sem PgBouncer transaction/session pooling, sem Octane/worker long-lived), então não há como um GUC de request anterior sobreviver para a próxima. A mitigação real é arquitetural (conexão nova por request + limpeza em `finally`/`terminate()`), não o desenho da policy. Se o projeto adotar PgBouncer em modo transaction/session pooling, Laravel Octane, ou qualquer worker de longa duração que reutilize conexões Postgres, este risco deixa de ser teórico e a policy OR precisa ser revisitada (ex.: limpar ambos os GUCs no início de toda request, não só o que a rota atual usa).
