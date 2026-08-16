---
paths:
  - 'database/migrations/**,app/Services/Chamado/**,app/Services/Mensagem/**'
---

# Mensagem

## Policies RLS de `chamados` se combinam via OR — considerar as duas antes de novo INSERT
A tabela `chamados` tem duas RLS policies permissivas, ambas `FOR ALL` sem `WITH CHECK` explícito (reaproveitam a expressão `USING` como `WITH CHECK` no INSERT, por padrão do Postgres): `chamados_isolamento_sistema` (sistema_id = app.current_sistema_id) e `chamados_sistemas_permitidos_atendente` (de CHAT-006, para o fluxo de atendente externo). Policies permissivas são combinadas com OR — ou seja, o `WITH CHECK` efetivo de um INSERT hoje é `(sistema_id = app.current_sistema_id) OR (sistema_id IN app.sistemas_permitidos_atendente)`.

Ao escrever qualquer novo caminho de INSERT em `chamados` (ou futura tabela `mensagens` com RLS análoga), não assuma que só a policy de isolamento por sistema protege a escrita — confirme qual GUC está setado na sessão/transação e que nenhum dos dois GUCs fica "vazado" de uma request anterior numa conexão reaproveitada. Verificado empiricamente em 2026-08-15 durante CHAT-008 (achado do security-reviewer): um INSERT cross-sistema real foi tentado e rejeitado pelo Postgres, confirmando que a policy de isolamento cobre INSERT — mas a coexistência das duas policies é motivo de atenção antes de CHAT-010 (fila) tocar esta tabela de novo.
