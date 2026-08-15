# Contrato do token assinado — sistema integrado → chat service

Este documento é normativo. Ele define o que um sistema integrado precisa emitir para que o chat service confie na identidade do usuário final.

**O chat service nunca autentica o usuário final.** Ele não tem senha, não tem tela de login para o cliente e não consulta a base de usuários de ninguém. Ele apenas valida um token que o sistema de origem já emitiu. Autenticar o usuário é responsabilidade exclusiva de cada sistema integrado.

Público-alvo: times que mantêm um sistema integrado ao chat (o "emissor"). Este documento não descreve como implementar a emissão dentro do seu sistema — qualquer biblioteca de JWT com suporte a RS256 serve.

---

## 1. Pré-requisito: estar cadastrado

Antes de emitir qualquer token, o sistema precisa estar registrado no chat service com:

| Campo | Descrição |
| --- | --- |
| `codigo` | Identificador único e estável do sistema (ex.: `gestao-oficinas`). É o valor que vai na claim `iss`. |
| `nome` | Nome legível. |
| `jwks_url` | URL **https** de onde o chat service busca as chaves públicas do sistema. |
| `status` | `ativo` ou `inativo`. |

O cadastro é feito pelos endpoints administrativos do chat service, protegidos por uma API key própria (`CHAT_ADMIN_API_KEY`), sem relação com o token descrito aqui.

Duas consequências que valem destaque:

- **A URL do JWKS vem sempre do cadastro do sistema**, nunca de configuração fixa do chat service. Cada sistema publica as próprias chaves e as gira quando quiser.
- **Se o `iss` não corresponder a um sistema cadastrado, ou corresponder a um sistema com status `inativo`, o token é inválido.** Essa checagem acontece antes da verificação de assinatura: sem um sistema ativo não há JWKS para consultar, então não há o que verificar. Desativar um sistema derruba o acesso dele imediatamente, sem depender da expiração dos tokens já emitidos.

## 2. Formato do token

Um JWT compacto (`header.payload.assinatura`), enviado no header HTTP:

```
Authorization: Bearer <token>
```

### 2.1 Header

| Campo | Obrigatório | Valor |
| --- | --- | --- |
| `alg` | sim | `RS256`. Único algoritmo aceito. |
| `typ` | sim | `JWT`. |
| `kid` | sim | Identificador da chave usada na assinatura. Precisa existir no JWKS do sistema. |

`alg` **não** é negociável pelo token: o chat service compara o valor com `RS256` e rejeita qualquer outro. Em particular, `none` e algoritmos HMAC (`HS256` e afins) são rejeitados sem sequer tentar verificar a assinatura — aceitar HMAC permitiria a qualquer um forjar tokens usando a chave pública do JWKS, que por definição é pública, como se fosse o segredo compartilhado.

`kid` é obrigatório porque é o que permite escolher a chave certa quando o sistema tem mais de uma publicada (o caso normal durante uma rotação).

`typ` ausente ou diferente de `JWT` invalida o token. A RFC 7519 trata `typ` como opcional, mas toda biblioteca de JWT o emite por padrão, e exigi-lo custa nada e deixa explícito que tipo de token é aquele.

### 2.2 Claims obrigatórias

A ausência de qualquer uma delas torna o token inválido, mesmo que a assinatura confira.

| Claim | Tipo | Conteúdo |
| --- | --- | --- |
| `iss` | string | O `codigo` do sistema emissor, exatamente como cadastrado. |
| `sub` | string | Identificador do usuário **dentro do sistema de origem**. A identidade no chat é o par `(iss, sub)` — dois sistemas podem ter um usuário `4213` sem colisão. |
| `aud` | string | Literal `chat-service`. **String, não array** — a RFC 7519 permite `aud` como lista, este contrato não: `["chat-service", "outro"]` é rejeitado. Não varia por ambiente: o token é emitido para o chat service como serviço, não para uma instalação dele. |
| `scope` | string | Permissões, separadas por espaço (ver §2.4). |
| `iat` | int | Unix timestamp da emissão. |
| `exp` | int | Unix timestamp da expiração. |

`iat` é obrigatório porque é o que permite ao chat service impor o teto de validade (§2.5). Sem ele, um emissor poderia mandar um `exp` a um ano de distância.

**Os tipos da tabela acima são exigidos, não coagidos.** `"exp": "1786500600"` — timestamp serializado como string em vez de número JSON — é o erro mais comum de emissor, e invalida o token. Da mesma forma, uma claim obrigatória presente porém vazia (`"scope": ""`, `"sub": ""`) é tratada como inválida, não como ausente: o emissor mandou o campo, só que sem conteúdo utilizável.

### 2.3 Claims opcionais

| Claim | Tipo | Conteúdo |
| --- | --- | --- |
| `role` | string | `cliente` ou `atendente`. Ausente equivale a `cliente`. Ver §2.3.1. |
| `cliente_unificado_ref` | string | Referência do cliente no cadastro unificado. Ver §2.3.2. |
| `jti` | string | Identificador único do token. **Reservado** para proteção contra reuso; hoje não é verificado. |

Claims adicionais não previstas aqui são ignoradas — inclusive `nbf`, que **não faz parte deste contrato**: não emita esperando que seja respeitada. A janela de validade é definida só por `iat` e `exp`.

Não coloque dado sensível no payload: um JWT é **assinado, não criptografado** — qualquer um que veja o token lê o conteúdo.

#### 2.3.1 `role`

**Os valores aceitos hoje são `cliente` e `atendente`.** A claim pode ser omitida (equivale a `cliente`). Qualquer outro valor **invalida o token**.

`role` é uma claim de privilégio cujo conteúdo é definido inteiramente pelo sistema emissor — por isso o papel **nunca é conferido pela claim isolada**: um token com `role=atendente` faz o chat service resolver ou provisionar (just-in-time, sem cadastro prévio de e-mail/senha) a linha em `atendentes` e o vínculo em `atendente_sistema` do sistema emissor (`iss`), a partir do `sub` do próprio token — a claim indica qual verificação/fluxo seguir, ela não é a verificação. Qualquer sistema integrado cadastrado e ativo pode emitir `role=atendente`; não há flag adicional de aprovação no cadastro do sistema — a garantia é a mesma pipeline RS256/JWKS/sistema-ativo que protege o restante do contrato.

**A identidade do atendente externo é `sub` sozinho, sem escopo por `iss`** — diferente do cliente final, onde a identidade é o par `(iss, sub)` (§2.2). Isso é deliberado: a API core externa usa o mesmo `sub` para a mesma pessoa em qualquer sistema integrado, e o mesmo atendente humano autenticando por dois sistemas diferentes precisa continuar sendo o mesmo registro `Atendente` no chat service — o vínculo com cada sistema adicional é acumulado em `atendente_sistema`, não gera uma segunda identidade. **Risco aceito, decisão do usuário**: como a correlação não é escopada por sistema, um sistema integrado comprometido que emita um `sub` colidente com o de um atendente já vinculado a outro sistema ganha vínculo com a identidade existente daquele atendente, herdando o que ele já tem acesso via os demais sistemas vinculados — mitigado só pela mesma garantia de confiança no emissor (cadastro ativo + RS256/JWKS), sem verificação adicional de unicidade de `sub` entre emissores.

Em código: `ContratoTokenCliente::rolesConhecidos()` é o vocabulário completo, `rolesAceitos()` é o que passa hoje (os dois, desde CHAT-005B). O provisionamento do atendente externo é `App\Services\Atendente\ProvisionarAtendenteExternoService`.

#### 2.3.2 `cliente_unificado_ref`

Esta claim é a **única** forma de correlacionar o mesmo cliente entre sistemas diferentes. O chat service **nunca** infere que dois clientes são a mesma pessoa por heurística — CPF, e-mail, telefone, nome ou qualquer combinação disso. Se o sistema não emitir a claim, os clientes permanecem separados, e isso é o comportamento correto.

O valor precisa ser **estável ao longo do tempo** para o mesmo cliente: é a chave de correlação, e mudá-la quebra o histórico já vinculado. Não use nele um identificador que o sistema recicla ou regenera.

**A correlação nunca amplia o escopo de leitura do token.** Um token continua limitado ao `sistema_id` do seu próprio `iss`, com ou sem `cliente_unificado_ref`. A claim serve à visão consolidada de atendente e admin (CHAT-021); ela **não** dá ao cliente final acesso a chamados ou mensagens de outro sistema.

Isso importa porque o valor é escolhido inteiramente pelo emissor: se correlacionar concedesse leitura cruzada, um sistema integrado comprometido emitiria um token com o `cliente_unificado_ref` de um cliente de outro sistema e leria dados que não são dele. O isolamento por `sistema_id` não tem exceção — nem por esta claim.

### 2.4 Vocabulário de `scope`

Formato: string única, valores separados por espaço (estilo OAuth 2.0).

| Scope | Permite |
| --- | --- |
| `chat:ler` | Ler chamados e mensagens do próprio usuário. |
| `chat:escrever` | Abrir chamados e enviar mensagens. |

Exemplo: `"scope": "chat:ler chat:escrever"`.

A claim é obrigatória e **não pode ser vazia**: `"scope": ""` invalida o token (§4), não equivale a "sem permissão nenhuma". Scopes desconhecidos são ignorados, mas um token só com scopes desconhecidos não conseguirá fazer nada.

### 2.5 Tempo de vida

| Regra | Valor |
| --- | --- |
| TTL recomendado na emissão | **10 minutos** (600s) |
| TTL máximo aceito (`exp - iat`) | **15 minutos** (900s) |
| Tolerância de dessincronia de relógio | 60s |

Um token com `exp - iat` acima de 900s é rejeitado **mesmo dentro da validade**. O token é de vida curta por desenho: ele serve para abrir a sessão de chat, não para ser guardado. Emita um novo quando o anterior estiver perto de expirar.

**`iat` no futuro, além da tolerância de 60s, invalida o token.** Sem essa regra o teto de TTL não valeria nada: bastaria emitir `iat = agora + 1 ano` e `exp = iat + 600` para que `exp - iat` continuasse dentro do limite e o token ficasse válido por um ano. As duas regras — teto de `exp - iat` e `iat` não estar adiante — só funcionam juntas.

A tolerância de 60s é aplicada às comparações de tempo (`exp` e `iat`) para absorver diferença de relógio entre o emissor e o chat service. Mantenha os servidores sincronizados por NTP; não conte com a tolerância como margem de projeto.

## 3. Endpoint JWKS

O sistema emissor publica as chaves públicas em um endpoint que o chat service busca na URL cadastrada em `jwks_url`.

Requisitos para quem publica:

- **https obrigatório.** URLs `http://` são recusadas já no cadastro.
- Caminho recomendado (não obrigatório): `/.well-known/jwks.json`.
- `Content-Type: application/json`.
- Acessível publicamente pelo chat service, sem autenticação.
- Chaves RSA de **no mínimo 2048 bits**. Uma chave abaixo disso é recusada, e um token assinado com ela é inválido.
- No máximo **10 chaves** e **64 KB** de resposta.

Formato ([RFC 7517](https://datatracker.ietf.org/doc/html/rfc7517)):

```json
{
  "keys": [
    {
      "kty": "RSA",
      "use": "sig",
      "alg": "RS256",
      "kid": "gestao-oficinas-2026-08",
      "n": "<módulo em base64url>",
      "e": "AQAB"
    }
  ]
}
```

Todos os campos acima são obrigatórios em cada chave. Chaves com `kty` diferente de `RSA` ou `alg` diferente de `RS256` são ignoradas.

### 3.1 Rotação de chave

O chat service faz cache do JWKS. Para girar uma chave sem derrubar tokens em trânsito:

1. Gere o novo par e publique a chave nova no JWKS **junto** com a antiga, cada uma com seu `kid`.
2. Passe a assinar com a nova (`kid` novo no header).
3. Mantenha a antiga publicada por, no mínimo, o TTL máximo do token (15 minutos) — na prática, deixe algumas horas.
4. Remova a antiga.

Nunca reutilize um `kid` para uma chave diferente. Sugestão de nomenclatura: `<codigo-do-sistema>-<ano>-<mês>`.

Um `kid` que não é encontrado no JWKS invalida o token. Publicar a chave **antes** de começar a assinar com ela evita a janela em que o chat service ainda tem o JWKS antigo em cache.

### 3.2 Cache e refetch (normativo para a implementação)

Validar um token é o caminho mais quente do produto. Buscar o JWKS por request adicionaria 50–300ms de RTT contra um servidor de terceiro em toda mensagem trocada, então o comportamento de cache faz parte do contrato, não é detalhe de implementação:

| Regra | Valor |
| --- | --- |
| TTL do JWKS em cache | 600s |
| TTL do cache negativo (JWKS que falhou) | 60s |
| Intervalo mínimo entre refetch por `kid` desconhecido, por sistema | 60s |

O cache negativo existe para não martelar um emissor fora do ar a cada request: uma falha na busca vale por 60s antes de nova tentativa.

**Refetch em `kid` desconhecido é permitido, com limite.** Sem refetch, uma rotação legítima só passaria a funcionar depois de o TTL expirar. Com refetch irrestrito, um atacante manda `kid` aleatório e transforma cada request nossa em uma request HTTP contra o servidor do integrador — amplificação. A regra é: ao encontrar um `kid` fora do JWKS em cache, refazer a busca **no máximo uma vez a cada 60s por sistema**, e usar lock para que requests concorrentes não disparem buscas simultâneas (stampede). Fora dessa janela, o token é rejeitado com o JWKS que já está em cache.

### 3.3 Busca segura do JWKS

`jwks_url` é uma URL de terceiro que o chat service busca server-side, o que é superfície de SSRF. A implementação precisa:

- Timeout de **2s para conectar** e **5s no total**.
- Limitar a leitura a **64 KB**, abortando respostas maiores em vez de bufferizar.
- Aceitar no máximo **10 chaves**; descartar o excedente.
- **Verificar o IP resolvido antes de conectar, em toda conexão** — a inicial e cada redirect. Recusar endereço privado ou link-local: `127.0.0.0/8`, `10/8`, `172.16/12`, `192.168/16`, `169.254/16` (inclui o endpoint de metadados de cloud `169.254.169.254`) e equivalentes IPv6. Checar só os redirects deixaria passar um `jwks_url` cujo hostname já resolve direto para um endereço interno. Só https, em toda a cadeia.
- **Conectar ao IP que foi validado**, não resolver o hostname de novo na hora de conectar. Entre a checagem e a conexão, uma resposta de DNS com TTL curto pode trocar o endereço por um interno (DNS rebinding); validar um IP e conectar em outro não protege nada.
- Nunca ecoar corpo ou header da resposta do JWKS em mensagem de erro devolvida ao cliente.

### 3.4 Cache do cadastro do sistema

A §1 promete que desativar um sistema derruba o acesso **imediatamente**, e a validação consulta o cadastro antes de checar a assinatura — o que seria um `SELECT` por request autenticado no endpoint mais quente do produto.

A implementação deve resolver isso cacheando o registro do sistema com **invalidação explícita**, não com TTL: os endpoints administrativos que alteram `status` ou `jwks_url` invalidam a entrada daquele sistema (`Cache::forget`) ao gravar. TTL quebraria a promessa de "imediato" pela metade do TTL; `SELECT` por request paga latência à toa. Invalidação explícita entrega as duas coisas.

## 4. Motivos de invalidez

Lista fechada: se não está aqui, não invalida o token. Quem implementa a validação não deve inventar rejeição fora desta tabela, nem deixar de aplicar alguma delas.

A coluna **Onde** diz o que basta para detectar cada motivo: `token` é observável no próprio JWT, `cadastro` exige consultar a tabela `sistemas`, `JWKS` exige a resposta do endpoint do emissor. Quem for escrever os testes de CHAT-005 precisa disso — só os de `token` se testam com um JWT isolado.

| Motivo | Onde | Detalhe |
| --- | --- | --- |
| Formato inválido | token | Não é um JWT compacto de três partes, ou header/payload não são JSON. |
| Algoritmo não suportado | token | `alg` ausente ou diferente de `RS256` (inclui `none` e HMAC). |
| `typ` inválido | token | `typ` ausente ou diferente de `JWT`. |
| `kid` ausente | token | Header sem `kid`. |
| Claim obrigatória ausente | token | Falta `iss`, `sub`, `aud`, `scope`, `iat` ou `exp`. |
| Claim com tipo inválido | token | Claim presente com tipo diferente do da §2.2 (ex.: `"exp"` como string, `"aud"` como array). Não há coerção. |
| Claim obrigatória vazia | token | Claim de tipo string presente porém vazia ou só com espaços (ex.: `"scope": ""`). |
| Audiência inválida | token | `aud` diferente de `chat-service`. |
| Expirado | token | `exp` no passado, já descontada a tolerância de 60s. |
| `iat` no futuro | token | `iat` adiante do relógio do chat service além da tolerância de 60s (§2.5). |
| TTL acima do teto | token | `exp - iat` maior que 900s. |
| `role` não aceita | token | `role` presente com valor diferente de `cliente` ou `atendente` (§2.3.1). |
| `iss` não cadastrado | cadastro | Nenhum sistema com esse `codigo`. |
| Sistema inativo | cadastro | O sistema existe, mas está com status `inativo`. |
| `kid` não encontrado | JWKS | Nenhuma chave com esse `kid` no JWKS do sistema. |
| Assinatura inválida | JWKS | A assinatura não confere com a chave do `kid`. |
| Chave abaixo do mínimo | JWKS | A chave usada na verificação tem menos de 2048 bits (§3). |
| JWKS inacessível | JWKS | Não foi possível obter o JWKS do sistema, ou a resposta violou os limites da §3.3. |

Os motivos de `token` têm exemplo executável em `GeradorTokenTeste` (§5.3). Os de `cadastro` e `JWKS` dependem de estado externo ao JWT e só ganham teste completo em CHAT-005 — o que existe hoje são os insumos: tokens estruturalmente perfeitos com `iss` não cadastrado ou de sistema inativo, e um JWKS publicando chave de 1024 bits.

Duas observações sobre a ordem de avaliação, que não é livre:

- **`alg` e `kid` são avaliados antes da assinatura.** Sem algoritmo permitido e sem chave resolvível não há verificação possível — e é exatamente isso que barra a confusão de algoritmo descrita na §2.1.
- **Uma claim com tipo errado não passa pelas regras de valor.** `"exp": "1786500600"` é rejeitada pelo tipo; não se tenta converter para número e depois checar expiração.

O chat service não distingue esses motivos na resposta ao cliente — todos resultam na mesma rejeição genérica, para não entregar a um atacante um oráculo sobre qual parte do token está errada. Os motivos ficam registrados no log do chat service.

## 5. Exemplos

### 5.1 Token válido

Header:

```json
{
  "alg": "RS256",
  "typ": "JWT",
  "kid": "chat-service-teste-2026"
}
```

Payload:

```json
{
  "iss": "gestao-oficinas",
  "sub": "4213",
  "aud": "chat-service",
  "scope": "chat:ler chat:escrever",
  "iat": 1786500000,
  "exp": 1786500600
}
```

Forma compacta (assinado com a chave **de teste** do repositório; `exp` em 2026-08-12T02:10:00Z, portanto **já expirado** — serve só para ilustrar o formato, não como token utilizável):

```
eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6ImNoYXQtc2VydmljZS10ZXN0ZS0yMDI2In0.eyJpc3MiOiJnZXN0YW8tb2ZpY2luYXMiLCJzdWIiOiI0MjEzIiwiYXVkIjoiY2hhdC1zZXJ2aWNlIiwic2NvcGUiOiJjaGF0OmxlciBjaGF0OmVzY3JldmVyIiwiaWF0IjoxNzg2NTAwMDAwLCJleHAiOjE3ODY1MDA2MDB9.XV2kbe6efV3eNMO2q2CQjkAe8p7Ighe0pgLeBcg_-zqbaCcU6TkNSn6MtX-DsQ-TiOFo838WeScvPp7ijXtAviMhzJ1xnp1XagTq9RCeBBns0t_zdmqVKLREl60LdqdpANTUMNSFYX-c1KcjEd23qwoMxpu_wKYvHSmMtCXgH-5AtrPLNvrSQ50IO1xKkgIkoEat8ShZ0olUk5eDUekWmAjG0QKUh1OFLS9kA-xOwPC_oIH4joJxdsDwq7t87NcTBWqrOshrOkIlYFqKiBIIEzuhZDaAc4TDxUooainbwGq8basI6STxdWvfsDYftM-KcQnt1WqijARY_N_RBU54xg
```

### 5.2 Tokens inválidos

Um exemplo por motivo, cada um violando exatamente um item da §4:

| Exemplo | Viola |
| --- | --- |
| Token com 2 ou 5 partes, ou payload que não é JSON | Formato inválido |
| Payload sem `iss` (idem para `sub`, `aud`, `scope`, `iat`, `exp`) | Claim obrigatória ausente |
| `"scope": ""` | Claim obrigatória vazia |
| `"exp": "1786500600"` (string) | Claim com tipo inválido |
| `"aud": ["chat-service", "outro"]` (array) | Claim com tipo inválido |
| `"aud": "outro-servico"` | Audiência inválida |
| `"exp"` no passado | Expirado |
| `"iat"` uma hora à frente, com `"exp" = "iat" + 600` | `iat` no futuro |
| `"exp" - "iat" == 901` | TTL acima do teto |
| `"role": "supervisor"` | `role` não aceita |
| Assinado com uma chave privada que não está no JWKS | Assinatura inválida |
| Header sem `kid` | `kid` ausente |
| `"kid": "kid-que-nao-esta-no-jwks"` | `kid` não encontrado |
| `"typ": "JWS"` | `typ` inválido |
| `"alg": "none"` e assinatura vazia | Algoritmo não suportado |
| `"alg": "HS256"` assinado por HMAC com a chave pública como segredo | Algoritmo não suportado |
| JWKS publicando chave RSA de 1024 bits | Chave abaixo do mínimo |
| Token perfeito com `iss` de sistema nunca cadastrado | `iss` não cadastrado |
| Token perfeito com `iss` de sistema com status `inativo` | Sistema inativo |

Os dois últimos são estruturalmente impecáveis: a assinatura confere e todas as claims estão lá. A rejeição vem da consulta ao cadastro, não do token. O caso da chave de 1024 bits também não é observável no token — a fraqueza está no conjunto de chaves publicado pelo sistema, e é detectada ao buscar o JWKS.

O exemplo do `iat` no futuro merece atenção de quem for implementar: ele passa em todas as outras regras temporais (`exp` está longe de expirar, `exp - iat` está dentro do teto) e só é pego pela regra específica da §2.5.

### 5.3 Onde estão os exemplos executáveis

Ficam em `tests/`, e são reaproveitados pelos testes de validação:

| Caminho | Conteúdo |
| --- | --- |
| `tests/Support/GeradorTokenTeste.php` | Gera o token válido e um por motivo de invalidez. |
| `tests/Fixtures/Token/chave-privada-teste.pem` | Chave usada para assinar os exemplos. |
| `tests/Fixtures/Token/chave-publica-teste.pem` | Par público correspondente. |
| `tests/Fixtures/Token/chave-privada-outro-emissor-teste.pem` | Chave fora do JWKS, para o caso de assinatura inválida. |
| `tests/Fixtures/Token/jwks-teste.json` | JWKS de exemplo, no formato da §3. |
| `tests/Fixtures/Token/chave-privada-fraca-teste.pem` | Chave RSA de 1024 bits, abaixo do mínimo. |
| `tests/Fixtures/Token/jwks-chave-fraca-teste.json` | JWKS publicando a chave fraca, para o motivo "chave abaixo do mínimo". |

As chaves versionadas existem **exclusivamente para a suíte de testes**. Nenhuma delas é aceita em qualquer ambiente real e nenhuma protege coisa alguma.

Os tokens são gerados em tempo de execução, não versionados como strings: `exp` é relativo ao agora, e um JWT versionado expiraria e quebraria a suíte.

## 6. Contrato em código

Os valores normativos deste documento existem em código, e é de lá que a validação lê — não repita literais:

| Onde | O quê |
| --- | --- |
| `app/Enums/ClaimTokenCliente.php` | Nomes das claims, quais são obrigatórias e o tipo JSON de cada uma. |
| `app/Support/ContratoTokenCliente.php` | Audiência, algoritmo, `typ`, TTLs, tolerância de relógio, papéis conhecidos vs. aceitos, vocabulário de scope, tamanho mínimo de chave, e os parâmetros de cache, refetch e busca do JWKS (§3.2 e §3.3). |

Alterar qualquer valor destes é alterar o contrato publicado: exige atualizar este documento e avisar os times emissores.

---

**Escopo deste documento (CHAT-004):** define o contrato. A implementação da validação é CHAT-005 e a emissão dentro de cada sistema integrado é responsabilidade do time que o mantém.
