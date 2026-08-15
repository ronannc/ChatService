export const meta = {
  name: 'plan-task',
  description: 'Lê a tarefa/épico do ChatService, faz análise estática do código sob 3 ângulos em paralelo e sintetiza um plano único com decisões em aberto',
  phases: [
    { title: 'Contexto' },
    { title: 'Planejar' },
    { title: 'Sintetizar' },
  ],
}

const CONTEXT_SCHEMA = {
  type: 'object',
  required: ['criterio_aceite', 'convencoes_relevantes', 'areas_tocadas'],
  properties: {
    criterio_aceite: { type: 'string', description: 'Resumo do critério de aceite da tarefa/épico, conforme a spec no ClickUp' },
    convencoes_relevantes: { type: 'string', description: 'Convenções de .ai/rules e CLAUDE.md aplicáveis a esta mudança' },
    areas_tocadas: { type: 'string', description: 'Camadas/arquivos existentes que esta mudança provavelmente toca (controllers, services, models, migrations)' },
    riscos_dominio: { type: 'string', description: 'Riscos de domínio conhecidos (isolamento por sistema_id, auth, RLS) relevantes a esta tarefa, se houver' },
  },
}

const PLAN_SCHEMA = {
  type: 'object',
  required: ['resumo', 'passos', 'perguntas_abertas'],
  properties: {
    resumo: { type: 'string' },
    passos: { type: 'array', items: { type: 'string' } },
    riscos: { type: 'array', items: { type: 'string' } },
    perguntas_abertas: { type: 'array', items: { type: 'string' }, description: 'Decisões que só o usuário pode tomar' },
  },
}

const SYNTHESIS_SCHEMA = {
  type: 'object',
  required: ['plano_final', 'perguntas_abertas'],
  properties: {
    plano_final: {
      type: 'object',
      required: ['resumo', 'passos'],
      properties: {
        resumo: { type: 'string' },
        passos: { type: 'array', items: { type: 'string' } },
        riscos: { type: 'array', items: { type: 'string' } },
      },
    },
    perguntas_abertas: { type: 'array', items: { type: 'string' } },
    justificativa: { type: 'string', description: 'Por que este enfoque venceu e quais ideias dos outros ângulos foram enxertadas' },
  },
}

const tarefa = args
if (!tarefa) {
  throw new Error('plan-task requer args: a tarefa/épico do ChatService a planejar (ex: "CHAT-007")')
}

phase('Contexto')
const contexto = await agent(
  `Você está planejando a tarefa/épico ChatService: "${tarefa}".
Passo 1: busque a spec dessa tarefa no ClickUp (Space "Chat Service", doc "Regras de Negócio — Serviço de Chat de Suporte" e as tasks do roadmap CHAT-XXX) usando as ferramentas do ClickUp disponíveis.
Passo 2: leia CLAUDE.md, .ai/rules/index.md e os rule files cujos globs cubram a área provável desta tarefa, e faça grep em .ai/rules por termos relevantes.
Passo 3: explore o código existente (app/, database/migrations/) para entender o que já existe nessa área.
Não escreva nem edite nenhum arquivo — isso é só levantamento de contexto.`,
  { schema: CONTEXT_SCHEMA, phase: 'Contexto' },
)

phase('Planejar')
const ANGULOS = [
  { key: 'mvp', foco: 'entregar o menor incremento que já cumpre o critério de aceite, sem generalizar além do que a tarefa pede' },
  { key: 'risco', foco: 'priorizar isolamento por sistema_id (global scope + RLS) e os dois mecanismos de auth (JWT cliente / Sanctum atendente) — que ponto dessa tarefa é mais perigoso de errar e como blindar' },
  { key: 'convencao', foco: 'seguir à risca os padrões já estabelecidos no código (Service por ação em app/Services, FormRequest, factories) e apontar onde a tarefa se desvia do padrão existente se for o caso' },
]

const planos = await parallel(ANGULOS.map((a) => () =>
  agent(
    `Tarefa/épico ChatService: "${tarefa}".
Contexto levantado:
- Critério de aceite: ${contexto.criterio_aceite}
- Convenções relevantes: ${contexto.convencoes_relevantes}
- Áreas tocadas: ${contexto.areas_tocadas}
- Riscos de domínio: ${contexto.riscos_dominio || 'nenhum específico levantado'}

Rascunhe um plano de implementação para esta tarefa com foco em: ${a.foco}.
Não escreva código. Liste passos concretos (arquivos/camadas a criar ou alterar) e, separadamente, toda decisão que dependa de escolha do usuário (não invente a resposta).`,
    { label: `plano:${a.key}`, phase: 'Planejar', schema: PLAN_SCHEMA },
  )
))

phase('Sintetizar')
const planosValidos = planos.filter(Boolean)
const sintese = await agent(
  `Aqui estão ${planosValidos.length} planos independentes para a mesma tarefa ChatService ("${tarefa}"), cada um sob um ângulo diferente:

${planosValidos.map((p, i) => `--- Plano ${i + 1} (ângulo: ${ANGULOS[i].key}) ---\n${JSON.stringify(p, null, 2)}`).join('\n\n')}

Sintetize um único plano final: escolha o enfoque geral mais sólido e enxerte as melhores ideias dos outros ângulos (principalmente riscos de isolamento/auth do ângulo "risco" e aderência a convenções do ângulo "convencao"). Junte e deduplique as perguntas em aberto de todos os planos.`,
  { schema: SYNTHESIS_SCHEMA, phase: 'Sintetizar' },
)

return { tarefa, contexto, planos: planosValidos, sintese }
