---
paths:
  - 'app/Http/Middleware/IdentificarAtendenteMensagem.php,app/Services/Mensagem/**'
---

# Services Mensagem

## Mensagens não cobrem atendente externo multi-sistema (CHAT-005B)
CHAT-009 só autentica atendente interno (Sanctum, single sistema_id próprio) nas rotas de mensagens. Não existe policy RLS `mensagens_sistemas_permitidos_atendente` nem GUC de sistemas_permitidos_atendente sendo usado aqui — só a policy `mensagens_isolamento_sistema` já existente.

Quando atendente externo (JWT com claim `role=atendente`, multi-sistema) precisar enviar/ler mensagens, será preciso: (1) uma terceira via de identificação dual-auth — hoje `IdentificarClienteMensagem` aceita qualquer JWT de 3 segmentos como cliente, inclusive um emitido com `role=atendente`, sem checar essa claim (não é brecha de autorização hoje, pois cai em 403 por cliente_ref não bater, mas precisa de teste explícito); e (2) uma policy RLS permissiva análoga a `chamados_sistemas_permitidos_atendente` para `mensagens`.

Contexto: rotas POST/GET /chamados/{id}/mensagens usam duas classes de middleware dual-auth (IdentificarClienteMensagem / IdentificarAtendenteMensagem) que decidem qual mecanismo tentar pelo FORMATO do token (JWT de 3 segmentos vs. Sanctum), não misturando a lógica de validação de cada mecanismo — necessário porque a mesma URI/método atende os dois tipos de ator e o Laravel não permite duas rotas para a mesma uri+method com middleware diferente.
