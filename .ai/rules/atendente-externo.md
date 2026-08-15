---
paths:
  - 'app/Models/Atendente.php'
  - 'app/Services/Atendente/**'
  - 'database/migrations/2026_08_15_004236_alter_atendentes_add_origem_externa_fields.php'
---

# Atendente externo (CHAT-005B)

`atendentes` tem dois `origem` (`interno`/`externo`, `App\Enums\OrigemAtendente`). Interno = CHAT-005A, Sanctum, e-mail/senha obrigatórios. Externo = CHAT-005B, mesmo JWT/JWKS do cliente final (`role=atendente`), provisionado just-in-time por `ProvisionarAtendenteExternoService` — por isso `email`/`senha` são nullable e existe `sub_externo`.

## Identidade é `sub_externo` sozinho, não `(sistema_id, sub_externo)`

Decisão do usuário: **a identidade do atendente externo é `sub_externo` sozinho**, sem escopo por `sistema_id` — diferente do cliente final, onde a identidade é `(iss, sub)`. Motivo: a API core externa usa o mesmo `sub` para a mesma pessoa em qualquer sistema integrado, então o mesmo atendente humano autenticando via dois sistemas diferentes precisa continuar sendo o MESMO registro `Atendente`, não dois. `sistema_id` na tabela `atendentes` é só o sistema do primeiro provisionamento (a "home"), não participa mais da identidade. Unique parcial é `atendentes_sub_externo_unique` em `sub_externo` sozinho (`WHERE sub_externo IS NOT NULL`) — nunca reponha o composto com `sistema_id`, e nunca reaproveite o unique de `email` para isso.

Quem acumula os múltiplos sistemas de um mesmo atendente é `atendente_sistema`: `ProvisionarAtendenteExternoService` garante o vínculo `(atendente_id, sistema_id=iss)` a cada chamada, mesmo em atendentes já existentes.

### Risco aceito — não é um bug, é uma consequência assumida

Como a correlação não é escopada por sistema, **um sistema integrado comprometido que emita um `sub` colidente com o de um atendente já vinculado a OUTRO sistema ganha vínculo (via `atendente_sistema`) com a identidade existente daquele atendente**, herdando o que esse atendente externo já tem acesso via os demais sistemas vinculados. Isso foi confirmado 2x pelo usuário como consequência aceita do modelo de confiança no emissor (cadastro ativo + pipeline RS256/JWKS já é a garantia; não há verificação adicional de unicidade de `sub` entre emissores). Se alguém propuser "resolver" isso sozinho sem consultar o usuário de novo, pare — é uma decisão de produto já tomada, não um defeito a corrigir silenciosamente.

**Efeito concreto, não só abstrato** (coberto por teste do qa em `AtendenteExternoTest`): depois da colisão, a resposta de `GET /api/atendente-externo/me` autenticada pelo sistema B passa a incluir o **código do sistema A** em `sistemas_permitidos` (`AtendenteContext::sistemasPermitidos()`, via `atendente_sistema`). Ou seja, o sistema B — sem nunca ter cadastro nem relação administrativa com o sistema A — descobre que o sistema A existe e está integrado ao chat service, só emitindo um `sub` colidente. Isso é vazamento de existência/código de outro sistema integrado, não apenas "herança de acesso" em abstrato — deixe isso em mente ao revisar qualquer endpoint que exponha `sistemas_permitidos()` no futuro.

## Por que o lookup precisa ignorar o global scope/RLS por sistema (mas só o lookup)

Como a busca de `Atendente` por `sub_externo` não pode mais ser restrita ao `sistema_id` do contexto atual (o atendente pode ter sido provisionado originalmente por outro sistema), `ProvisionarAtendenteExternoService` usa `Atendente::withoutGlobalScopes()` + `SistemaContext::GUC_BYPASS_RESOLUCAO_ATENDENTE` via `SET LOCAL` dentro de uma `DB::transaction()` — o mesmo mecanismo de `LoginAtendenteService::buscarPorEmailBypassandoRls()`, escopado só a essa consulta/criação, não à request inteira.

Isso é diferente de `EnableAtendenteAuthRlsBypass` (middleware que liga o bypass pra request inteira, usado só pelo fluxo Sanctum/CHAT-005A) — **não** ligue esse middleware pro fluxo externo. `EnsureValidTokenCliente` já resolveu `SistemaContext::set($iss)` antes do provisionamento rodar; o insert em si (quando o atendente é novo) satisfaz a RLS normalmente porque `sistema_id` gravado é sempre o `iss` do token corrente. O bypass só é necessário para achar uma linha cuja `sistema_id` (home) é de OUTRO sistema.

**Cuidado ao chamar este Service de dentro de outra transação.** O escopo de `SET LOCAL` hoje é "só a query" na prática porque o `DB::transaction()` dentro de `ProvisionarAtendenteExternoService::handle()` é sempre a transação mais externa (o Service nunca é chamado de dentro de outro `DB::transaction()` aberto). Se isso mudar no futuro, o `DB::transaction()` do Laravel, ao detectar uma transação já aberta, cria um SAVEPOINT em vez de uma transação nova — e `SET LOCAL` dentro de um savepoint **sobrevive ao `RELEASE SAVEPOINT`**, só sendo desfeito no commit/rollback da transação externa como um todo. Nesse cenário o bypass vazaria silenciosamente para o resto da transação externa (outras queries depois deste Service, na mesma requisição, passariam a ignorar a RLS de `atendentes`). Antes de aninhar este Service dentro de outra transação, revise esse ponto — não assuma que o escopo continua sendo só a query.

## Idempotência

Lookup-then-create (`firstOrCreate`), nunca catch de exceção de unique constraint como mecanismo primário — a constraint é rede de segurança contra corrida, não o caminho normal.
