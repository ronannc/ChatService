---
description: Spawna o agent team do ChatService (dev, QA, PO, performance, segurança) para trabalhar numa tarefa/épico
argument-hint: [tarefa ou épico, ex: CHAT-005]
---

Spawn um agent team para trabalhar em: $ARGUMENTS

Se `$ARGUMENTS` estiver vazio, pergunte qual tarefa/épico do roadmap (ver CLAUDE.md e a memória de domínio do ChatService) devemos atacar antes de spawnar qualquer teammate.

Teammates a spawnar, usando os agent types já definidos em `.claude/agents/`:

- `dev-laravel` → nomeie **dev**
- `qa-pest` → nomeie **qa**
- `po-validator` → nomeie **po**
- `perf-specialist` → nomeie **perf**
- `security-reviewer` → nomeie **security**

## Regras de coordenação (você como team lead)

1. Exija plan approval do `dev` antes de qualquer alteração de código — só aprove planos que incluam cobertura de teste e que respeitem o isolamento por `sistema_id` (dupla camada: global scope Eloquent + Row Level Security).
2. Depois que `dev` implementar, peça para `qa`, `po`, `perf` e `security` revisarem o trabalho em paralelo e reportarem achados.
3. Se algum revisor levantar um problema, peça para `dev` responder diretamente à objeção — concordando e corrigindo, ou justificando por que não procede — antes de considerar a tarefa fechada. Silêncio não é resolução.
4. `po` só dá o veredito final (aprovado / aprovado com ressalvas / reprovado) depois que `qa` confirmar os testes passando e `security` não ter achados críticos em aberto.
5. Me avise (com um resumo de 3-5 linhas) sempre que: (a) o plano do `dev` estiver pronto para minha aprovação, (b) houver divergência entre revisores que precise da minha decisão, (c) o time considerar a tarefa concluída.
6. Não faça `git push` nem abra PR sem minha aprovação explícita.
