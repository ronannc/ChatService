---
description: Spawna o agent team do ChatService (dev, QA, PO, performance, segurança) para trabalhar numa tarefa/épico
argument-hint: [tarefa ou épico, ex: CHAT-005]
---

Tarefa/épico: $ARGUMENTS

Se `$ARGUMENTS` estiver vazio, pergunte qual tarefa/épico do roadmap (ver CLAUDE.md e a memória de domínio do ChatService) devemos atacar antes de spawnar qualquer teammate.

## Antes de montar o time completo

Nem toda tarefa justifica os 5 teammates — cada um é uma sessão independente com contexto próprio, e o custo escala com quantos ficam ativos. Antes de spawnar, decida:

- **Time completo (dev+qa+po+perf+security)**: qualquer épico do roadmap CHAT-XXX, ou qualquer mudança que toque autenticação (JWT do cliente / Sanctum do atendente), isolamento por `sistema_id` (global scope + RLS), endpoints administrativos, ou upload de mídia. Nessas áreas a revisão de `security`/`po` não é opcional — a criticidade do domínio manda mais que a economia de tokens aqui.
- **Só `dev` (sem time)**: correção pontual, ajuste de estilo, tarefa mecânica ou já coberta por teste existente sem mudar regra de negócio. Diga isso ao usuário antes de seguir sem time.
- Na dúvida entre os dois, pergunte ao usuário em vez de assumir o time completo por padrão.

## Passo 0: planejar antes de montar o time

Antes de spawnar qualquer teammate para um épico do roadmap (ou qualquer mudança não-trivial), rode o workflow salvo `plan-task` passando a tarefa/épico em `args` (ex: `Workflow({name: 'plan-task', args: 'CHAT-007'})`). Ele busca a spec no ClickUp, faz análise estática do código sob 3 ângulos em paralelo (MVP, risco de domínio, aderência a convenções) e sintetiza um plano único + lista de decisões em aberto.

Isso substitui o `dev` como autor do plano: o `dev` deixa de *desenhar* o plano do zero e passa a *executar* o plano já sintetizado (aprovado por você) — se ao implementar descobrir algo que o plano não previu, ele sinaliza a divergência em vez de replanejar silenciosamente.

- Responda às perguntas em aberto que o workflow trouxer antes de aprovar o plano.
- Para uma correção pontual/mecânica (ver critério de "só dev" abaixo), pular esse passo é aceitável — o overhead de planejar não compensa.

## Como montar

Teammates a spawnar, usando os agent types já definidos em `.claude/agents/` (cada um já tem `model: sonnet` fixado no frontmatter — não precisa especificar modelo ao spawnar, e não leia o arquivo do papel manualmente, referencie pelo agent type):

- `dev-laravel` → nomeie **dev**
- `qa-pest` → nomeie **qa**
- `po-validator` → nomeie **po**
- `perf-specialist` → nomeie **perf**
- `security-reviewer` → nomeie **security**

### Posse de arquivo (evita colisão dev × qa)

`dev` e `qa` são os únicos dois teammates com permissão de escrita, e sem fronteira isso colide: `dev` implementa a feature e também poderia mexer em teste, `qa` escreve teste. Isso já é reforçado por um hook `PreToolUse` no frontmatter de `dev-laravel.md`/`qa-pest.md` — bloqueio determinístico, não só instrução no prompt:

- `dev` possui `app/**`, `database/migrations/**`, `database/factories/**` — não cria nem edita arquivos em `tests/**` (o hook bloqueia); se precisar de um teste mínimo para validar a própria implementação, descreve o cenário e pede para `qa` escrever.
- `qa` possui `tests/**` — não edita código de aplicação (o hook bloqueia); se encontrar um bug ao testar, reporta para `dev` em vez de corrigir direto.

### Workflow em vez de time completo

Para uma varredura grande e única — auditar todos os endpoints do roadmap por um mesmo problema, por exemplo — prefira pedir um **workflow** (script que orquestra dezenas de subagentes em background) em vez de montar o time de 5. O time serve para uma feature/épico com papéis fixos revisando o mesmo diff; o workflow serve para "aplicar a mesma checagem em N lugares".

## Grafo de dependências (por que a ordem abaixo não é arbitrária)

`perf`, `po` e `security` só têm trabalho real depois que existe um diff de `dev` para analisar — a aresta `dev → revisores` é real (o input deles é o output dele), não uma sequência por hábito. Os 4 revisores, uma vez com o diff em mãos, não dependem uns dos outros — devem correr em paralelo de verdade, não um de cada vez.

1. Antes de qualquer alteração de código, exija um plano aprovado — vindo do workflow `plan-task` (passo 0) para épicos/tarefas não-triviais, ou redigido pelo próprio `dev` para o caso "só dev". Só aprove planos que incluam cobertura de teste (a cargo de `qa`) e que respeitem o isolamento por `sistema_id` (dupla camada: global scope Eloquent + Row Level Security).
2. Depois que `dev` implementar, peça para `qa`, `po`, `perf` e `security` revisarem o trabalho **em paralelo** (mensagem para os 4 de uma vez, não sequencial) e reportarem achados.
3. Se algum revisor levantar um problema, peça para `dev` responder diretamente à objeção — concordando e corrigindo, ou justificando por que não procede — antes de considerar a tarefa fechada. Silêncio não é resolução.
4. **Guarda contra loop caro**: se o mesmo par dev↔revisor trocar objeção/resposta sobre o **mesmo ponto mais de 2 vezes** sem convergir, pare o ciclo e me escale a divergência em vez de deixar rodar — cada rodada carrega contexto crescente para os dois lados.
5. `po` só dá o veredito final (aprovado / aprovado com ressalvas / reprovado) depois que `qa` confirmar os testes passando e `security` não ter achados críticos em aberto.
6. Me avise (com um resumo de 3-5 linhas) sempre que: (a) o plano do `dev` estiver pronto para minha aprovação, (b) houver divergência entre revisores que precise da minha decisão, (c) o time considerar a tarefa concluída.
7. Não faça `git push` nem abra PR sem minha aprovação explícita.
