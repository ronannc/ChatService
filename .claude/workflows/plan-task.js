export const meta = {
  name: 'plan-task',
  description: 'Lê a tarefa/épico do ChatService, levanta contexto e gera um plano único cobrindo mínimo viável, risco de domínio e aderência a convenções',
  phases: [
    { title: 'Contexto' },
    { title: 'Planejar' },
  ],
}

const CONTEXT_SCHEMA = {
  type: 'object',
  required: ['criterio_aceite', 'convencoes_relevantes', 'areas_tocadas'],
  properties: {
    criterio_aceite: { type: 'string', description: 'Resumo do critério de aceite da tarefa/épico, conforme a spec no ClickUp' },
    convencoes_relevantes: { type: 'string', description: 'Convenções de .ai/rules e CLAUDE.md aplicáveis a esta mudança. Se esta mudança toca um Service que já tem um "irmão" próximo no mesmo domínio (ex. outro Service em app/Services/Chamado/**), inclua aqui, de forma explícita e como item obrigatório (não sugestão), todo padrão estrutural de defesa em profundidade que o irmão já usa (ex. whereIn por sistema_id somado a withoutGlobalScope) — não deixe essa checagem só para os revisores acharem depois.' },
    areas_tocadas: { type: 'string', description: 'Camadas/arquivos existentes que esta mudança provavelmente toca (controllers, services, models, migrations)' },
    riscos_dominio: { type: 'string', description: 'Riscos de domínio conhecidos (isolamento por sistema_id, auth, RLS) relevantes a esta tarefa, se houver' },
    subtarefas: { type: 'array', items: { type: 'string' }, description: 'Se a tarefa buscada no ClickUp for um épico com subtarefas, liste cada uma como "CHAT-XXX — nome" (id + nome). Vazio se não houver ou não for aplicável.' },
    clickup_indisponivel: { type: 'boolean', description: 'true se o conector ClickUp não estava autenticado/acessível e você teve que parar a busca cedo (ver instrução de fail-fast no prompt)' },
  },
}

// Um único plano, mas obrigado a endereçar as 3 lentes como campos separados —
// substitui os 3 agentes paralelos + síntese: nas execuções reais (CHAT-008/009)
// os 3 ângulos convergiam no mesmo desenho e mal usavam ferramentas (1-8 tool
// calls cada), ou seja, só reformulavam o mesmo contexto sob um enquadramento
// diferente. Um agente só, instruído a preencher as 3 seções, reproduz o
// mesmo resultado por ~metade do custo em tokens/tempo.
const PLAN_SCHEMA = {
  type: 'object',
  required: ['resumo', 'minimo_viavel', 'risco_dominio', 'aderencia_convencoes', 'passos', 'perguntas_abertas'],
  properties: {
    resumo: { type: 'string' },
    minimo_viavel: { type: 'string', description: 'Qual é o menor incremento que já cumpre o critério de aceite sem generalizar além do que a tarefa pede — o que fica explicitamente fora de escopo' },
    risco_dominio: { type: 'string', description: 'Isolamento por sistema_id (global scope + RLS) e os mecanismos de auth (JWT cliente / Sanctum atendente): qual ponto desta tarefa é mais perigoso de errar e como blindar' },
    aderencia_convencoes: { type: 'string', description: 'Quais padrões já estabelecidos no código (Service por ação, FormRequest, factories) esta tarefa deve seguir, e onde ela se desvia do padrão existente, se for o caso' },
    passos: { type: 'array', items: { type: 'string' }, description: 'Passos concretos: arquivos/camadas a criar ou alterar, em ordem' },
    riscos: { type: 'array', items: { type: 'string' } },
    perguntas_abertas: { type: 'array', items: { type: 'string' }, description: 'Decisões que só o usuário pode tomar — não invente a resposta' },
  },
}

const tarefa = args
if (!tarefa) {
  throw new Error('plan-task requer args: a tarefa/épico do ChatService a planejar (ex: "CHAT-007")')
}

phase('Contexto')
const contexto = await agent(
  `Você está planejando a tarefa/épico ChatService: "${tarefa}".

Passo 1 (fail-fast, faça UMA tentativa só): tente localizar a tarefa no ClickUp (Space "Chat Service", doc "Regras de Negócio — Serviço de Chat de Suporte" e as tasks do roadmap CHAT-XXX) com 1-2 chamadas de busca direta (ex. clickup_search pelo código da tarefa). Se a ferramenta não estiver autenticada/disponível ou a primeira busca falhar por esse motivo, PARE de tentar ClickUp imediatamente — não fique tentando variações de busca nem tente compensar isso explorando o repositório a fundo em busca de pistas. Marque \`clickup_indisponivel: true\` e siga com o que você tiver: se "${tarefa}" já contém texto de spec colado pelo usuário (não só um código curto tipo "CHAT-011"), use-o como fonte primária e trate como confiável. Se não tiver nem ClickUp nem texto colado, preencha os campos com o mínimo inferível do próprio código e sinalize claramente nas perguntas em aberto que a spec não foi confirmada contra a fonte de verdade — não simule confiança que você não tem.
Se a tarefa for um épico com subtarefas, liste-as no campo \`subtarefas\`.

Passo 2 (só se passo 1 teve sucesso ou "${tarefa}" já veio com spec colada — não vale a pena gastar tool calls nisso só pra descobrir que não há spec): leia CLAUDE.md, .ai/rules/index.md e os rule files cujos globs cubram a área provável desta tarefa, e faça grep em .ai/rules por termos relevantes.

Passo 3: explore o código existente (app/, database/migrations/) para entender o que já existe nessa área. Se houver um Service "irmão" no mesmo domínio (mesmo diretório em app/Services/), leia-o e note explicitamente qualquer padrão de defesa em profundidade (ex. filtro client-side por sistema_id somado a withoutGlobalScope) que a nova tarefa deveria replicar.

Não escreva nem edite nenhum arquivo — isso é só levantamento de contexto.`,
  { schema: CONTEXT_SCHEMA, phase: 'Contexto' },
)

phase('Planejar')
const plano = await agent(
  `Tarefa/épico ChatService: "${tarefa}".
Contexto levantado:
- Critério de aceite: ${contexto.criterio_aceite}
- Convenções relevantes: ${contexto.convencoes_relevantes}
- Áreas tocadas: ${contexto.areas_tocadas}
- Riscos de domínio: ${contexto.riscos_dominio || 'nenhum específico levantado'}

Rascunhe UM plano de implementação para esta tarefa que endereça explicitamente as 3 lentes abaixo — preencha cada uma como um campo separado do schema, não pule nenhuma:
1. Mínimo viável: qual o menor incremento que já cumpre o critério de aceite, sem generalizar além do que a tarefa pede.
2. Risco de domínio: isolamento por sistema_id (global scope + RLS) e os dois mecanismos de auth (JWT cliente / Sanctum atendente) — qual ponto desta tarefa é mais perigoso de errar e como blindar.
3. Aderência a convenções: quais padrões já estabelecidos no código (Service por ação em app/Services, FormRequest, factories) esta tarefa deve seguir, e onde ela se desvia do padrão existente, se for o caso.

Não escreva código. Liste passos concretos (arquivos/camadas a criar ou alterar) e, separadamente, toda decisão que dependa de escolha do usuário (não invente a resposta).`,
  { schema: PLAN_SCHEMA, phase: 'Planejar' },
)

return { tarefa, contexto, plano }
